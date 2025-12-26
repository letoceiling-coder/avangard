<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    private string $apiUrl;
    private ?string $apiToken;
    private string $domain;
    private int $cacheTtl;

    public function __construct()
    {
        $this->apiUrl = config('subscription.api_url', 'https://crm.siteaccess.ru/api/v1/subscription/check');
        $this->apiToken = config('subscription.api_token');
        $this->domain = request()->getHost();
        $this->cacheTtl = config('subscription.cache_ttl', 120); // 2 минуты по умолчанию
    }

    /**
     * Проверка подписки через API
     */
    public function checkSubscription(): ?array
    {
        if (!$this->apiToken) {
            Log::warning('Subscription API token not configured');
            return null;
        }

        // Используем кэш для уменьшения нагрузки на API
        $cacheKey = 'subscription_status_' . md5($this->domain . $this->apiToken);
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->apiUrl, [
                        'domain' => $this->domain
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    Log::info('Subscription check successful', [
                        'domain' => $this->domain,
                        'status' => $data['status'] ?? 'unknown',
                        'is_active' => $data['is_active'] ?? false,
                    ]);
                    
                    // Сохраняем успешный результат как последний известный статус
                    $lastCacheKey = 'subscription_status_' . md5($this->domain . $this->apiToken) . '_last';
                    Cache::put($lastCacheKey, $data, 3600); // Храним 1 час
                    
                    return $data;
                }

                // Обработка ошибок
                $statusCode = $response->status();
                
                if ($statusCode === 401) {
                    Log::error('Subscription API: Unauthorized - invalid API token', [
                        'domain' => $this->domain,
                    ]);
                } elseif ($statusCode === 404) {
                    Log::warning('Subscription API: Subscription not found', [
                        'domain' => $this->domain,
                    ]);
                } elseif ($statusCode === 429) {
                    Log::warning('Subscription API: Rate limit exceeded', [
                        'domain' => $this->domain,
                    ]);
                    // При превышении лимита возвращаем последний известный статус из кэша
                    $lastCacheKey = 'subscription_status_' . md5($this->domain . $this->apiToken) . '_last';
                    return Cache::get($lastCacheKey);
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Subscription check failed', [
                    'domain' => $this->domain,
                    'error' => $e->getMessage(),
                ]);
                
                // При ошибке сети возвращаем последний известный статус
                $lastCacheKey = 'subscription_status_' . md5($this->domain . $this->apiToken) . '_last';
                return Cache::get($lastCacheKey);
            }
        });
    }

    /**
     * Проверка активности подписки
     */
    public function isActive(): bool
    {
        $data = $this->checkSubscription();
        return $data && ($data['is_active'] === true || $data['is_active'] === 'true');
    }

    /**
     * Проверка, истекает ли подписка скоро
     */
    public function isExpiringSoon(int $days = 3): bool
    {
        $data = $this->checkSubscription();
        if (!$data || !isset($data['expires_at'])) {
            return false;
        }

        try {
            $expiresAt = Carbon::parse($data['expires_at']);
            $daysUntilExpiry = now()->diffInDays($expiresAt, false);
            
            return $daysUntilExpiry <= $days && $daysUntilExpiry >= 0;
        } catch (\Exception $e) {
            Log::error('Failed to parse subscription expiry date', [
                'expires_at' => $data['expires_at'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Получить данные подписки
     */
    public function getSubscriptionData(): ?array
    {
        return $this->checkSubscription();
    }

    /**
     * Получить количество дней до истечения
     */
    public function getDaysUntilExpiry(): ?int
    {
        $data = $this->checkSubscription();
        if (!$data || !isset($data['expires_at'])) {
            return null;
        }

        try {
            $expiresAt = Carbon::parse($data['expires_at']);
            $daysUntilExpiry = now()->diffInDays($expiresAt, false);
            
            return $daysUntilExpiry >= 0 ? $daysUntilExpiry : null;
        } catch (\Exception $e) {
            Log::error('Failed to calculate days until expiry', [
                'expires_at' => $data['expires_at'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Очистить кэш подписки
     */
    public function clearCache(): void
    {
        $cacheKey = 'subscription_status_' . md5($this->domain . $this->apiToken);
        Cache::forget($cacheKey);
        Cache::forget($cacheKey . '_last');
    }
}

