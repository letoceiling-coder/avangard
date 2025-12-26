<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $apiBaseUrl = 'https://api.telegram.org/bot';

    /**
     * Получить информацию о боте
     */
    public function getBotInfo(string $token): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiBaseUrl . $token . '/getMe');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Неизвестная ошибка',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram getBotInfo error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Установить webhook
     */
    public function setWebhook(string $token, string $url, array $options = []): array
    {
        try {
            $params = array_merge([
                'url' => $url,
            ], $options);

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/setWebhook', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    return [
                        'success' => true,
                        'message' => 'Webhook успешно установлен',
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось установить webhook',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram setWebhook error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Получить информацию о webhook
     */
    public function getWebhookInfo(string $token): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiBaseUrl . $token . '/getWebhookInfo');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    $webhookInfo = $data['result'] ?? [];
                    
                    return [
                        'success' => true,
                        'data' => [
                            'url' => $webhookInfo['url'] ?? null,
                            'has_custom_certificate' => $webhookInfo['has_custom_certificate'] ?? false,
                            'pending_update_count' => $webhookInfo['pending_update_count'] ?? 0,
                            'last_error_date' => $webhookInfo['last_error_date'] ?? null,
                            'last_error_message' => $webhookInfo['last_error_message'] ?? null,
                            'max_connections' => $webhookInfo['max_connections'] ?? null,
                            'allowed_updates' => $webhookInfo['allowed_updates'] ?? [],
                        ],
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось получить информацию о webhook',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram getWebhookInfo error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Удалить webhook
     */
    public function deleteWebhook(string $token, bool $dropPendingUpdates = false): array
    {
        try {
            $params = [];
            if ($dropPendingUpdates) {
                $params['drop_pending_updates'] = true;
            }

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/deleteWebhook', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    return [
                        'success' => true,
                        'message' => 'Webhook успешно удален',
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось удалить webhook',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram deleteWebhook error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }
}

