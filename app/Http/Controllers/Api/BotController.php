<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BotController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $bots = Bot::orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $bots,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Получаем информацию о боте из Telegram
            $botInfo = $this->telegramService->getBotInfo($request->token);
            
            if (!$botInfo['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $botInfo['message'] ?? 'Не удалось получить информацию о боте',
                ], 400);
            }

            // Формируем webhook URL
            $webhookUrl = url('/api/telegram/webhook/' . $request->token);

            // Настройки webhook из запроса или дефолтные
            $allowedUpdates = $request->input('webhook.allowed_updates');
            if (is_string($allowedUpdates)) {
                $allowedUpdates = array_map('trim', explode(',', $allowedUpdates));
            }
            
            $webhookOptions = [
                'allowed_updates' => $allowedUpdates ?: config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                'max_connections' => $request->input('webhook.max_connections', config('telegram.webhook.max_connections', 40)),
            ];

            if ($request->has('webhook.secret_token') && $request->input('webhook.secret_token')) {
                $webhookOptions['secret_token'] = $request->input('webhook.secret_token');
            }

            // Регистрируем webhook
            $webhookResult = $this->telegramService->setWebhook($request->token, $webhookUrl, $webhookOptions);

            // Формируем настройки бота
            $settings = $request->settings ?? [];
            if ($request->has('webhook')) {
                $settings['webhook'] = [
                    'allowed_updates' => $webhookOptions['allowed_updates'],
                    'max_connections' => $webhookOptions['max_connections'],
                ];
                if (isset($webhookOptions['secret_token'])) {
                    $settings['webhook']['secret_token'] = $webhookOptions['secret_token'];
                }
            }

            // Создаем бота
            $bot = Bot::create([
                'name' => $request->name,
                'token' => $request->token,
                'username' => $botInfo['data']['username'] ?? null,
                'webhook_url' => $webhookUrl,
                'webhook_registered' => $webhookResult['success'] ?? false,
                'welcome_message' => $request->welcome_message ?? null,
                'settings' => $settings,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Бот успешно зарегистрирован',
                'data' => $bot,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании бота: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $bot,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'token' => 'sometimes|required|string',
            'welcome_message' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Если изменился токен, обновляем информацию о боте
            if ($request->has('token') && $request->token !== $bot->token) {
                $botInfo = $this->telegramService->getBotInfo($request->token);
                
                if (!$botInfo['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $botInfo['message'] ?? 'Не удалось получить информацию о боте',
                    ], 400);
                }

                // Обновляем webhook URL
                $webhookUrl = url('/api/telegram/webhook/' . $request->token);
                
                // Настройки webhook из запроса или дефолтные
                $allowedUpdates = $request->input('webhook.allowed_updates');
                if (is_string($allowedUpdates)) {
                    $allowedUpdates = array_map('trim', explode(',', $allowedUpdates));
                }
                
                $webhookOptions = [
                    'allowed_updates' => $allowedUpdates ?: config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                    'max_connections' => $request->input('webhook.max_connections', config('telegram.webhook.max_connections', 40)),
                ];

                if ($request->has('webhook.secret_token') && $request->input('webhook.secret_token')) {
                    $webhookOptions['secret_token'] = $request->input('webhook.secret_token');
                }

                $webhookResult = $this->telegramService->setWebhook($request->token, $webhookUrl, $webhookOptions);

                $bot->webhook_url = $webhookUrl;
                $bot->webhook_registered = $webhookResult['success'] ?? false;
                $bot->username = $botInfo['data']['username'] ?? null;
            }

            $bot->update($request->only([
                'name',
                'token',
                'welcome_message',
                'settings',
                'is_active',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Бот успешно обновлен',
                'data' => $bot->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении бота: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        try {
            // Удаляем webhook перед удалением бота
            $this->telegramService->deleteWebhook($bot->token);
            
            $bot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Бот успешно удален',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении бота: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Проверить установку webhook
     */
    public function checkWebhook(string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        try {
            $result = $this->telegramService->getWebhookInfo($bot->token);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Зарегистрировать webhook
     */
    public function registerWebhook(Request $request, string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        try {
            $webhookUrl = $bot->webhook_url ?: url('/api/telegram/webhook/' . $bot->token);
            
            // Настройки webhook из запроса или из настроек бота
            $settings = $bot->settings ?? [];
            $allowedUpdates = $request->input('allowed_updates');
            if (!$allowedUpdates && isset($settings['webhook']['allowed_updates'])) {
                $allowedUpdates = $settings['webhook']['allowed_updates'];
            }
            if (is_string($allowedUpdates)) {
                $allowedUpdates = array_map('trim', explode(',', $allowedUpdates));
            }
            
            $webhookOptions = [
                'allowed_updates' => $allowedUpdates ?: config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                'max_connections' => $request->input('max_connections', $settings['webhook']['max_connections'] ?? config('telegram.webhook.max_connections', 40)),
            ];

            $secretToken = $request->input('secret_token', $settings['webhook']['secret_token'] ?? null);
            if ($secretToken) {
                $webhookOptions['secret_token'] = $secretToken;
            }
            
            $result = $this->telegramService->setWebhook($bot->token, $webhookUrl, $webhookOptions);
            
            if ($result['success']) {
                $bot->update([
                    'webhook_url' => $webhookUrl,
                    'webhook_registered' => true,
                ]);
            }
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? ($result['success'] ? 'Webhook успешно зарегистрирован' : 'Ошибка регистрации webhook'),
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при регистрации webhook: ' . $e->getMessage(),
            ], 500);
        }
    }
}
