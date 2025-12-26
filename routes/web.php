<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ВАЖНО: Роут для storage должен быть ПЕРВЫМ, до всех остальных
// Это нужно, чтобы Laravel обрабатывал запросы к /storage, даже если символическая ссылка не работает
Route::get('/storage/{path}', function ($path) {
    // Защита от path traversal
    $path = str_replace('..', '', $path);
    $path = ltrim($path, '/');
    
    $filePath = storage_path('app/public/' . $path);
    $basePath = storage_path('app/public');
    
    // Проверяем, что файл находится внутри базовой директории
    $realFilePath = realpath($filePath);
    $realBasePath = realpath($basePath);
    
    if (!$realFilePath || !$realBasePath || !str_starts_with($realFilePath, $realBasePath)) {
        \Illuminate\Support\Facades\Log::warning('Storage file access denied - path traversal attempt', [
            'path' => $path,
            'file_path' => $filePath,
        ]);
        abort(404);
    }
    
    if (!file_exists($realFilePath) || !is_file($realFilePath)) {
        \Illuminate\Support\Facades\Log::warning('Storage file not found', [
            'path' => $path,
            'real_file_path' => $realFilePath,
        ]);
        abort(404);
    }
    
    $mimeType = mime_content_type($realFilePath);
    $fileName = basename($realFilePath);
    
    \Illuminate\Support\Facades\Log::debug('Storage file served', [
        'path' => $path,
        'file' => $fileName,
        'mime_type' => $mimeType,
    ]);
    
    return response()->file($realFilePath, [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*')->name('storage.serve');

// API маршруты должны быть обработаны до SPA маршрутов
// Они определены в routes/api.php

// Проксирование assets для React приложения - должно быть ПЕРВЫМ
// Если запрашивается /assets/*, отдаем из /frontend/assets/*
Route::get('/assets/{path}', function ($path) {
    // Безопасно получаем имя файла (защита от path traversal)
    $fileName = basename($path);
    $filePath = public_path('frontend/assets/' . $fileName);
    
    // Проверяем существование файла
    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404, "File not found: {$fileName}");
    }
    
    // Определяем MIME тип по расширению
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    $mimeType = $mimeTypes[$extension] ?? mime_content_type($filePath);
    
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.+')->name('react.assets');

// Страница истечения подписки (должна быть до админ-панели)
Route::get('/subscription-expired', [\App\Http\Controllers\SubscriptionExpiredController::class, 'index'])
    ->name('subscription.expired');

// Маршруты для админ-панели (Vue) - с проверкой подписки
Route::middleware('subscription.check')->group(function () {
Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*')->name('admin');
});

// Публичный роут для просмотра логов
Route::get('/logs', [\App\Http\Controllers\LogController::class, 'index'])->name('logs.index');

// Маршруты для основного приложения (React)
// Все остальные маршруты (кроме admin, api, storage, build, frontend, assets, logs) отдаются React приложению
Route::get('/{any?}', function ($any = null) {
    // Перед отдачей React view проверяем, не запрашивается ли статический файл
    if ($any && preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|webp|woff|woff2|ttf|eot)$/i', $any)) {
        $filePath = public_path($any);
        if (file_exists($filePath) && is_file($filePath)) {
            return response()->file($filePath);
        }
    }
    
    return view('react');
})->where('any', '^(?!admin|api|storage|build|frontend|assets|logs).*')->name('react');
