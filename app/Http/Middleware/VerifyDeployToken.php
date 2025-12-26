<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeployToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Получаем токен из заголовка или тела запроса
        $authHeader = $request->header('Authorization');
        $token = null;
        
        // Проверяем Authorization header с Bearer токеном
        if ($authHeader && preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
        
        // Fallback на другие заголовки
        $token = $token 
            ?? $request->header('X-Deploy-Token') 
            ?? $request->header('X-Deploy-Secret')
            ?? $request->input('token')
            ?? $request->input('secret');
        
        // Пытаемся получить ожидаемый токен из разных источников
        $expectedToken = config('app.deploy_token');
        
        // Если не найден в конфиге, читаем напрямую из .env
        if (!$expectedToken) {
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                
                // Сначала пробуем DEPLOY_TOKEN
                if (preg_match('/^DEPLOY_TOKEN=(.+)$/m', $envContent, $matches)) {
                    $expectedToken = trim($matches[1], ' "\'');
                }
                
                // Если не найден, пробуем DEPLOY_SECRET (для обратной совместимости)
                if (!$expectedToken && preg_match('/^DEPLOY_SECRET=(.+)$/m', $envContent, $matches)) {
                    $expectedToken = trim($matches[1], ' "\'');
                }
            }
        }
        
        // Если все еще не найден, пробуем env()
        if (!$expectedToken) {
            $expectedToken = env('DEPLOY_TOKEN') ?? env('DEPLOY_SECRET');
        }

        if (!$expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'DEPLOY_TOKEN или DEPLOY_SECRET не настроен на сервере. Добавьте DEPLOY_TOKEN в .env файл и выполните: php artisan config:clear',
            ], 500);
        }

        // Детальное логирование для диагностики (только для webhook поддержки)
        if (str_contains($request->path(), 'support/webhook')) {
            Log::channel('tickets')->debug('Deploy token check for support webhook', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'tokens_match' => $token === $expectedToken,
            ]);
        } else {
            Log::info('Проверка токена деплоя', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'received_token_length' => $token ? strlen($token) : 0,
                'expected_token_length' => $expectedToken ? strlen($expectedToken) : 0,
                'tokens_match' => $token === $expectedToken,
            ]);
        }
        
        if (!$token || $token !== $expectedToken) {
            // Логирование неверного токена для webhook поддержки
            if (str_contains($request->path(), 'support/webhook')) {
                Log::channel('tickets')->warning('Invalid deploy token for support webhook', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'user_agent' => $request->userAgent(),
                ]);
            } else {
                Log::warning('Попытка обновления с неверным секретным ключом', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'received_token' => $token ? substr($token, 0, 3) . '...' . substr($token, -3) : 'null',
                    'expected_token' => $expectedToken ? substr($expectedToken, 0, 3) . '...' . substr($expectedToken, -3) : 'null',
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Неверный секретный ключ',
                'debug' => config('app.debug') ? [
                    'received_length' => $token ? strlen($token) : 0,
                    'expected_length' => $expectedToken ? strlen($expectedToken) : 0,
                ] : null,
            ], 403);
        }

        return $next($request);
    }
}

