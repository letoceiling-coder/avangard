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

            // Формируем настройки бота
            $settings = $request->settings ?? [];
            if ($request->has('webhook')) {
                $allowedUpdates = $request->input('webhook.allowed_updates');
                if (is_string($allowedUpdates)) {
                    $allowedUpdates = array_map('trim', explode(',', $allowedUpdates));
                }
                
                $settings['webhook'] = [
                    'allowed_updates' => $allowedUpdates ?: config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                    'max_connections' => $request->input('webhook.max_connections', config('telegram.webhook.max_connections', 40)),
                ];
                if ($request->has('webhook.secret_token') && $request->input('webhook.secret_token')) {
                    $settings['webhook']['secret_token'] = $request->input('webhook.secret_token');
                }
            }

            // Создаем бота сначала без webhook URL
            $bot = Bot::create([
                'name' => $request->name,
                'token' => $request->token,
                'username' => $botInfo['data']['username'] ?? null,
                'webhook_url' => null, // Будет установлен после создания
                'webhook_registered' => false,
                'welcome_message' => $request->welcome_message ?? null,
                'settings' => $settings,
                'is_active' => true,
            ]);

            // Теперь формируем правильный webhook URL с ID бота
            $webhookUrl = url('/api/telegram/webhook/' . $bot->id);
            
            // Настройки webhook
            $webhookOptions = [
                'allowed_updates' => $settings['webhook']['allowed_updates'] ?? config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                'max_connections' => $settings['webhook']['max_connections'] ?? config('telegram.webhook.max_connections', 40),
            ];
            
            if (isset($settings['webhook']['secret_token'])) {
                $webhookOptions['secret_token'] = $settings['webhook']['secret_token'];
            }

            // Регистрируем webhook с правильным URL
            $webhookResult = $this->telegramService->setWebhook($bot->token, $webhookUrl, $webhookOptions);
            
            // Обновляем бота с правильным webhook URL
            $bot->webhook_url = $webhookUrl;
            $bot->webhook_registered = $webhookResult['success'] ?? false;
            $bot->save();

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

                // Обновляем webhook URL с ID бота
                $webhookUrl = url('/api/telegram/webhook/' . $bot->id);
                
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
     * Обработка webhook от Telegram
     */
    public function handleWebhook(Request $request, string $id): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('🔔 Webhook request received', [
            'bot_id' => $id,
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'raw_body' => $request->getContent(),
        ]);

        try {
            $bot = Bot::findOrFail($id);
            
            \Illuminate\Support\Facades\Log::info('✅ Bot found', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'bot_username' => $bot->username,
                'is_active' => $bot->is_active,
            ]);
            
            // Проверяем secret_token, если он установлен
            if (!empty($bot->settings['webhook']['secret_token'])) {
                $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
                if ($secretToken !== $bot->settings['webhook']['secret_token']) {
                    \Illuminate\Support\Facades\Log::warning('❌ Webhook secret token mismatch', [
                        'bot_id' => $bot->id,
                        'received_token' => $secretToken ? 'present' : 'missing',
                        'expected_token' => 'present',
                    ]);
                    return response()->json(['error' => 'Invalid secret token'], 403);
                }
                \Illuminate\Support\Facades\Log::info('✅ Secret token verified');
            }
            
            // Получаем обновление от Telegram
            $update = $request->all();
            
            \Illuminate\Support\Facades\Log::info('📨 Telegram update received', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'update_id' => $update['update_id'] ?? null,
                'message_type' => $this->getUpdateType($update),
                'update' => $update,
            ]);
            
            // Обработка сообщений
            if (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'] ?? null;
                $text = $message['text'] ?? null;
                
                \Illuminate\Support\Facades\Log::info('💬 Message received', [
                    'bot_id' => $bot->id,
                    'chat_id' => $chatId,
                    'text' => $text,
                    'from' => $message['from'] ?? null,
                ]);
                
                // Обработка команды /start
                if ($text === '/start' || str_starts_with($text, '/start')) {
                    \Illuminate\Support\Facades\Log::info('🚀 /start command received', [
                        'bot_id' => $bot->id,
                        'chat_id' => $chatId,
                    ]);
                    
                    // Отправляем приветственное сообщение
                    if ($bot->welcome_message) {
                        $this->telegramService->sendMessage(
                            $bot->token,
                            $chatId,
                            $bot->welcome_message
                        );
                        \Illuminate\Support\Facades\Log::info('✅ Welcome message sent', [
                            'bot_id' => $bot->id,
                            'chat_id' => $chatId,
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::info('ℹ️ No welcome message configured', [
                            'bot_id' => $bot->id,
                        ]);
                    }
                }
            }
            
            return response()->json(['ok' => true], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Illuminate\Support\Facades\Log::error('❌ Bot not found', [
                'bot_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Bot not found'], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Webhook processing error', [
                'bot_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Определить тип обновления
     */
    private function getUpdateType(array $update): string
    {
        if (isset($update['message'])) return 'message';
        if (isset($update['edited_message'])) return 'edited_message';
        if (isset($update['channel_post'])) return 'channel_post';
        if (isset($update['edited_channel_post'])) return 'edited_channel_post';
        if (isset($update['callback_query'])) return 'callback_query';
        if (isset($update['inline_query'])) return 'inline_query';
        if (isset($update['chosen_inline_result'])) return 'chosen_inline_result';
        if (isset($update['shipping_query'])) return 'shipping_query';
        if (isset($update['pre_checkout_query'])) return 'pre_checkout_query';
        if (isset($update['poll'])) return 'poll';
        if (isset($update['poll_answer'])) return 'poll_answer';
        if (isset($update['my_chat_member'])) return 'my_chat_member';
        if (isset($update['chat_member'])) return 'chat_member';
        if (isset($update['chat_join_request'])) return 'chat_join_request';
        return 'unknown';
    }

    /**
     * Зарегистрировать webhook
     */
    public function registerWebhook(Request $request, string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        try {
            // Всегда используем правильный URL с ID бота, игнорируя сохраненный в БД
            $webhookUrl = url('/api/telegram/webhook/' . $bot->id);
            
            \Illuminate\Support\Facades\Log::info('🔧 Registering webhook', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'webhook_url' => $webhookUrl,
                'current_webhook_url' => $bot->webhook_url,
            ]);
            
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
            
            \Illuminate\Support\Facades\Log::info('📤 Sending webhook registration to Telegram', [
                'bot_id' => $bot->id,
                'webhook_url' => $webhookUrl,
                'options' => $webhookOptions,
            ]);
            
            $result = $this->telegramService->setWebhook($bot->token, $webhookUrl, $webhookOptions);
            
            \Illuminate\Support\Facades\Log::info('📥 Telegram API response', [
                'bot_id' => $bot->id,
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? null,
                'data' => $result['data'] ?? null,
            ]);
            
            if ($result['success']) {
                $bot->update([
                    'webhook_url' => $webhookUrl,
                    'webhook_registered' => true,
                ]);
                \Illuminate\Support\Facades\Log::info('✅ Webhook registered successfully', [
                    'bot_id' => $bot->id,
                    'webhook_url' => $webhookUrl,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error('❌ Failed to register webhook', [
                    'bot_id' => $bot->id,
                    'webhook_url' => $webhookUrl,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? ($result['success'] ? 'Webhook успешно зарегистрирован' : 'Ошибка регистрации webhook'),
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Exception during webhook registration', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при регистрации webhook: ' . $e->getMessage(),
            ], 500);
        }
    }
}
