<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscriptionExpiredController extends Controller
{
    /**
     * Отображение страницы истечения подписки
     */
    public function index(Request $request)
    {
        $subscriptionData = session('subscription_data');
        
        // Если это API запрос, возвращаем JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Подписка истекла',
                'subscription' => $subscriptionData,
            ], 403);
        }

        return view('subscription.expired', [
            'subscription' => $subscriptionData,
        ]);
    }
}

