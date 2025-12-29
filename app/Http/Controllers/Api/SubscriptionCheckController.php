<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionCheckController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Проверка статуса подписки
     */
    public function check(Request $request)
    {
        // В локальной разработке всегда возвращаем успешную подписку
        if (app()->environment('local')) {
            return response()->json([
                'success' => true,
                'is_active' => true,
                'subscription' => [
                    'status' => 'active',
                    'expires_at' => now()->addYear()->toDateTimeString(),
                    'domain' => request()->getHost(),
                ],
                'is_expiring_soon' => false,
                'days_until_expiry' => 365,
            ]);
        }
        
        try {
            // Очищаем кеш для диагностики (можно убрать после выяснения проблемы)
            if ($request->has('clear_cache')) {
                $this->subscriptionService->clearCache();
            }

            $subscriptionData = $this->subscriptionService->getSubscriptionData();

            if (!$subscriptionData) {
                \Log::warning('SubscriptionCheck: No subscription data returned', [
                    'domain' => request()->getHost(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить данные о подписке. Проверьте логи и настройки SUBSCRIPTION_API_TOKEN',
                    'subscription' => null,
                    'is_active' => false,
                    'debug' => [
                        'domain' => request()->getHost(),
                        'api_url' => config('subscription.api_url'),
                        'has_token' => !empty(config('subscription.api_token')),
                    ],
                ], 404);
            }

            $isActive = $this->subscriptionService->isActive();
            $daysUntilExpiry = $this->subscriptionService->getDaysUntilExpiry();
            $isExpiringSoon = $this->subscriptionService->isExpiringSoon();

            \Log::info('SubscriptionCheck: Success', [
                'domain' => request()->getHost(),
                'is_active' => $isActive,
                'status' => $subscriptionData['status'] ?? 'unknown',
                'expires_at' => $subscriptionData['expires_at'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'subscription' => $subscriptionData,
                'is_active' => $isActive,
                'is_expiring_soon' => $isExpiringSoon,
                'days_until_expiry' => $daysUntilExpiry,
            ]);
        } catch (\Exception $e) {
            \Log::error('SubscriptionCheck: Exception', [
                'domain' => request()->getHost(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке подписки: ' . $e->getMessage(),
                'subscription' => null,
                'is_active' => false,
            ], 500);
        }
    }
}
