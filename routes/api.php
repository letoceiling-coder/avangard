<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminMenuController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\Api\v1\FolderController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\Api\TrendSsoController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\DataChangeController;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\ParserErrorController;
use App\Http\Controllers\Api\PriceHistoryController;
use App\Http\Controllers\Api\RegionController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Защищённые роуты
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    
    // Меню
    Route::get('/admin/menu', [AdminMenuController::class, 'index']);
    
    // Уведомления
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/all', [NotificationController::class, 'all']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    
    // Media API (v1)
    Route::prefix('v1')->group(function () {
        // Folders
        Route::get('folders/tree/all', [FolderController::class, 'tree'])->name('folders.tree');
        Route::post('folders/update-positions', [FolderController::class, 'updatePositions'])->name('folders.update-positions');
        Route::post('folders/{id}/restore', [FolderController::class, 'restore'])->name('folders.restore');
        Route::apiResource('folders', FolderController::class);
        
        // Media
        Route::post('media/{id}/restore', [MediaController::class, 'restore'])->name('media.restore');
        Route::delete('media/trash/empty', [MediaController::class, 'emptyTrash'])->name('media.trash.empty');
        Route::apiResource('media', MediaController::class);
        
        // Admin only routes (Roles and Users management)
        Route::middleware('admin')->group(function () {
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('users', UserController::class);
            
            // Bots management
            Route::apiResource('bots', BotController::class);
            Route::get('bots/{id}/check-webhook', [BotController::class, 'checkWebhook']);
            Route::post('bots/{id}/register-webhook', [BotController::class, 'registerWebhook']);
            
            // Bot Management (detailed management)
            Route::prefix('bot-management/{botId}')->group(function () {
                // Заявки
                Route::get('consultations', [\App\Http\Controllers\Api\BotManagementController::class, 'getConsultations']);
                Route::get('consultations/{id}', [\App\Http\Controllers\Api\BotManagementController::class, 'getConsultation']);
                Route::put('consultations/{id}', [\App\Http\Controllers\Api\BotManagementController::class, 'updateConsultation']);
                
                // Настройки
                Route::get('settings', [\App\Http\Controllers\Api\BotManagementController::class, 'getSettings']);
                Route::put('settings', [\App\Http\Controllers\Api\BotManagementController::class, 'updateSettings']);
                
                // Категории материалов
                Route::get('materials/categories', [\App\Http\Controllers\Api\BotManagementController::class, 'getMaterialCategories']);
                Route::post('materials/categories', [\App\Http\Controllers\Api\BotManagementController::class, 'storeMaterialCategory']);
                Route::put('materials/categories/{id}', [\App\Http\Controllers\Api\BotManagementController::class, 'updateMaterialCategory']);
                Route::delete('materials/categories/{id}', [\App\Http\Controllers\Api\BotManagementController::class, 'destroyMaterialCategory']);
                
                // Материалы
                Route::get('materials', [\App\Http\Controllers\Api\BotManagementController::class, 'getMaterials']);
                Route::post('materials', [\App\Http\Controllers\Api\BotManagementController::class, 'storeMaterial']);
                Route::put('materials/{id}', [\App\Http\Controllers\Api\BotManagementController::class, 'updateMaterial']);
                Route::delete('materials/{id}', [\App\Http\Controllers\Api\BotManagementController::class, 'destroyMaterial']);
                
                // Статистика
                Route::get('statistics', [\App\Http\Controllers\Api\BotManagementController::class, 'getStatistics']);
                
                // Пользователи
                Route::get('users', [\App\Http\Controllers\Api\BotManagementController::class, 'getUsers']);
            });
            
            // Support tickets
            Route::get('support/tickets', [SupportController::class, 'index']);
            Route::get('support/tickets/{id}', [SupportController::class, 'show']);
            Route::post('support/ticket', [SupportController::class, 'store']);
            Route::post('support/message', [SupportController::class, 'sendMessage']);
            
            // Trend Parser API - Blocks, Parkings, Parser Errors
            Route::apiResource('blocks', BlockController::class);
            Route::get('blocks/outdated', [BlockController::class, 'outdated']);
            Route::apiResource('parkings', ParkingController::class);
            Route::apiResource('parser-errors', ParserErrorController::class)->only(['index', 'show', 'update']);
            
            // Regions and Cities
            Route::get('regions', [RegionController::class, 'index']);
            Route::put('regions/cities/{city}', [RegionController::class, 'updateCity']);
            Route::put('regions/regions/{region}', [RegionController::class, 'updateRegion']);
            Route::post('regions/cities/bulk-update', [RegionController::class, 'bulkUpdateCities']);
            Route::post('regions/regions/bulk-update', [RegionController::class, 'bulkUpdateRegions']);
            
            // Data Changes and Price History
            Route::prefix('data-changes')->group(function () {
                Route::get('/', [DataChangeController::class, 'index']);
                Route::get('/statistics', [DataChangeController::class, 'statistics']);
                Route::get('/{type}/{id}', [DataChangeController::class, 'show']);
            });
            
            Route::prefix('price-history')->group(function () {
                Route::get('/', [PriceHistoryController::class, 'index']);
                Route::get('/statistics', [PriceHistoryController::class, 'statistics']);
                Route::get('/{type}/{id}', [PriceHistoryController::class, 'show']);
            });
        });
    });
});

// Integration API (protected by deploy.token middleware)
Route::middleware('deploy.token')->prefix('integration')->group(function () {
    Route::post('/messages', [\App\Http\Controllers\Api\IntegrationController::class, 'receiveMessage']);
    Route::post('/status', [\App\Http\Controllers\Api\IntegrationController::class, 'receiveStatusChange']);
});

// Legacy webhooks (deprecated, use /api/integration/*)
Route::middleware('deploy.token')->prefix('support/webhook')->group(function () {
    Route::post('/message', [SupportController::class, 'webhookMessage']);
    Route::post('/status', [SupportController::class, 'webhookStatus']);
});

// Маршрут для деплоя (защищен токеном)
Route::post('/deploy', [DeployController::class, 'deploy'])
    ->middleware('deploy.token');

// Маршрут для выполнения seeders (защищен токеном)
Route::post('/seed', [DeployController::class, 'seed'])
    ->middleware('deploy.token');

// Webhook от GitHub для автоматического деплоя (проверка подписи внутри контроллера)
Route::post('/webhook/github', [\App\Http\Controllers\Api\WebhookController::class, 'github']);

// Проверка подписки (публичный endpoint, используется фронтендом)
Route::get('/subscription/check', [\App\Http\Controllers\Api\SubscriptionCheckController::class, 'check']);

// Публичные роуты для просмотра логов
Route::get('/logs', [\App\Http\Controllers\LogController::class, 'getLogs']);
Route::get('/logs/files', [\App\Http\Controllers\LogController::class, 'getLogFilesList']);
Route::post('/logs/clear', [\App\Http\Controllers\LogController::class, 'clearLogs']);

// Telegram webhook (публичный роут, Telegram отправляет POST запросы)
Route::post('/telegram/webhook/{id}', [BotController::class, 'handleWebhook'])
    ->where('id', '[0-9]+')
    ->name('telegram.webhook');

// Trend SSO Parser API (protected routes)
Route::middleware('auth:sanctum')->prefix('trendsso')->group(function () {
    Route::post('/authenticate', [TrendSsoController::class, 'authenticate']);
    Route::post('/objects-list', [TrendSsoController::class, 'getObjectsList']);
});

