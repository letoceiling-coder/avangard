<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Пропускаем проверку для страницы истечения подписки, API endpoints и страниц авторизации
        if ($request->routeIs('subscription.expired') || 
            $request->is('api/*') ||
            $request->is('login') ||
            $request->is('register') ||
            $request->is('forgot-password') ||
            $request->is('reset-password') ||
            $request->is('subscription-expired')) {
            return $next($request);
        }

        $subscriptionData = $this->subscriptionService->getSubscriptionData();

        // Если подписка не найдена или неактивна
        if (!$subscriptionData || !$this->subscriptionService->isActive()) {
            // Сохраняем данные подписки в сессию для отображения
            session()->put('subscription_expired', true);
            session()->put('subscription_data', $subscriptionData);
            
            // Для API запросов возвращаем JSON ответ
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Подписка истекла или не найдена',
                    'subscription' => $subscriptionData,
                    'redirect' => route('subscription.expired'),
                ], 403);
            }
            
            // Редирект на страницу-заглушку
            return redirect()->route('subscription.expired');
        }

        // Сохраняем данные подписки для отображения в админ-панели
        session()->put('subscription_data', $subscriptionData);
        session()->put('subscription_expired', false);

        return $next($request);
    }
}

