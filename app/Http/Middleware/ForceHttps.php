<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\URL;

/**
 * Middleware для принудительного использования HTTPS
 * и исправления URL (удаление /public/)
 */
class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Пропускаем middleware для CLI команд (artisan)
        if (app()->runningInConsole()) {
            return $next($request);
        }
        
        // Дополнительная проверка: если Request не валиден, пропускаем
        try {
            $request->path();
        } catch (\Exception $e) {
            // Если не можем получить path, значит Request не валиден - пропускаем
            return $next($request);
        }
        
        // Логируем входящий запрос
        $path = $request->path();
        $fullUrl = $request->fullUrl();
        $appUrl = config('app.url');
        
        \Log::info('ForceHttps Middleware - Incoming Request', [
            'path' => $path,
            'full_url' => $fullUrl,
            'secure' => $request->secure(),
            'app_url' => $appUrl,
            'request_uri' => $request->getRequestUri(),
            'scheme' => $request->getScheme(),
            'host' => $request->getHost(),
        ]);
        
        // Принудительно используем HTTPS только в production и только для реальных доменов
        // Не применяем для локальных доменов (.loc, .local, localhost)
        $host = $request->getHost();
        $isLocalDomain = str_contains($host, '.loc') || 
                         str_contains($host, '.local') || 
                         $host === 'localhost' || 
                         str_starts_with($host, '127.0.0.1') ||
                         str_starts_with($host, '192.168.');
        
        // КРИТИЧНО: Исправляем URI, если он содержит /public/ - это должно быть ПЕРВЫМ
        $requestUri = $request->getRequestUri();
        if (str_contains($requestUri, '/public/')) {
            $fixedUri = str_replace('/public/', '/', $requestUri);
            $fixedUri = str_replace('/public', '', $fixedUri);
            \Log::info('ForceHttps Middleware - Fixing URI with /public/', [
                'original' => $requestUri,
                'fixed' => $fixedUri,
            ]);
            // Редиректим на исправленный URI
            return redirect($fixedUri, 301);
        }
        
        if (!$request->secure() && config('app.env') === 'production' && !$isLocalDomain) {
            $uri = $request->getRequestUri();
            // Убираем /public/ из URI, если он там есть
            $uri = str_replace('/public', '', $uri);
            \Log::info('ForceHttps Middleware - Redirecting to HTTPS', ['uri' => $uri]);
            return redirect()->secure($uri);
        }

        // Для локальных доменов явно сбрасываем принудительный HTTPS
        if ($isLocalDomain) {
            URL::forceScheme(null);
            \Log::info('ForceHttps Middleware - Local domain detected, forcing scheme to null', ['host' => $host]);
        }
        
        // Исправляем APP_URL, если он содержит /public/ или использует HTTP
        // Но только для production и не для локальных доменов
        $needsUpdate = false;
        
        if ($appUrl && !$isLocalDomain) {
            // Убираем /public/ из URL
            if (str_contains($appUrl, '/public')) {
                $appUrl = str_replace('/public', '', $appUrl);
                $needsUpdate = true;
                \Log::info('ForceHttps Middleware - Removing /public/ from APP_URL', ['old' => config('app.url'), 'new' => $appUrl]);
            }
            
            // Заменяем HTTP на HTTPS только в production
            if (str_starts_with($appUrl, 'http://') && config('app.env') === 'production') {
                $appUrl = str_replace('http://', 'https://', $appUrl);
                $needsUpdate = true;
                \Log::info('ForceHttps Middleware - Replacing http:// with https://', ['old' => config('app.url'), 'new' => $appUrl]);
            }
            
            if ($needsUpdate) {
                config(['app.url' => $appUrl]);
                // Устанавливаем базовый URL для генерации URL
                URL::forceRootUrl($appUrl);
                // Принудительно используем HTTPS только в production
                if (config('app.env') === 'production') {
                    URL::forceScheme('https');
                }
                \Log::info('ForceHttps Middleware - Updated APP_URL', [
                    'app_url' => config('app.url'),
                    'forced_root_url' => $appUrl,
                    'forced_scheme' => config('app.env') === 'production' ? 'https' : 'auto',
                ]);
            }
        }
        
        // ВАЖНО: Убеждаемся, что APP_URL не содержит /public/ даже если он был установлен ранее
        // Это нужно делать всегда, не только при обновлении
        $currentAppUrl = config('app.url');
        if ($currentAppUrl && str_contains($currentAppUrl, '/public') && !$isLocalDomain) {
            $cleanedAppUrl = str_replace('/public', '', $currentAppUrl);
            config(['app.url' => $cleanedAppUrl]);
            URL::forceRootUrl($cleanedAppUrl);
            if (config('app.env') === 'production') {
                URL::forceScheme('https');
            }
            \Log::info('ForceHttps Middleware - Cleaned APP_URL (always check)', [
                'old' => $currentAppUrl,
                'new' => $cleanedAppUrl,
            ]);
        }

        $response = $next($request);

        // КРИТИЧНО: Исправляем URL в ответе, если они содержат /public/
        // Это нужно для HTML ответов, где могут быть ссылки с /public/
        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $content = $response->getContent();
            if ($content && str_contains($content, '/public/')) {
                // Убираем /public/ из всех URL в HTML
                $fixedContent = str_replace('/public/', '/', $content);
                $fixedContent = str_replace('"/public', '"/', $fixedContent);
                $fixedContent = str_replace("'/public", "'/", $fixedContent);
                $fixedContent = str_replace('href="/public', 'href="/', $fixedContent);
                $fixedContent = str_replace("href='/public", "href='/", $fixedContent);
                $fixedContent = str_replace('src="/public', 'src="/', $fixedContent);
                $fixedContent = str_replace("src='/public", "src='/", $fixedContent);
                $fixedContent = str_replace('url("/public', 'url("/', $fixedContent);
                $fixedContent = str_replace("url('/public", "url('/", $fixedContent);
                
                // Исправляем baseURI в скриптах
                $fixedContent = preg_replace('/document\.baseURI\s*=\s*["\']([^"\']*\/public\/[^"\']*)["\']/', 'document.baseURI = "' . str_replace('/public/', '/', '$1') . '"', $fixedContent);
                
                $response->setContent($fixedContent);
                \Log::info('ForceHttps Middleware - Fixed /public/ in HTML response');
            }
        }

        // Логируем после обработки
        \Log::info('ForceHttps Middleware - After processing', [
            'app_url' => config('app.url'),
            'response_status' => $response->getStatusCode(),
            'request_uri' => $request->getRequestUri(),
            'path' => $request->path(),
        ]);

        return $response;
    }
}

