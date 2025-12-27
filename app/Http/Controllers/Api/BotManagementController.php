<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\StoreMaterialCategoryRequest;
use App\Http\Requests\UpdateBotSettingsRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Bot;
use App\Models\BotConsultation;
use App\Models\BotMaterial;
use App\Models\BotMaterialCategory;
use App\Models\BotUser;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BotManagementController extends Controller
{
    /**
     * Получить список заявок
     */
    public function getConsultations(Request $request, string $botId): JsonResponse
    {
        $bot = Bot::findOrFail($botId);
        
        $query = BotConsultation::where('bot_id', $botId)
            ->with('botUser');

        // Фильтр по статусу
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Фильтр по дате
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min($request->get('per_page', 20), 100);
        $consultations = $query->paginate($perPage);

        // Загружаем пользователей для каждой заявки
        $consultations->getCollection()->transform(function ($consultation) use ($botId) {
            $consultation->user = BotUser::where('bot_id', $botId)
                ->where('telegram_user_id', $consultation->telegram_user_id)
                ->first();
            return $consultation;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'consultations' => $consultations->items(),
                'total' => $consultations->total(),
                'filters' => [
                    'status' => $request->status,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ],
            ],
        ]);
    }

    /**
     * Получить детали заявки
     */
    public function getConsultation(string $botId, string $id): JsonResponse
    {
        $consultation = BotConsultation::where('bot_id', $botId)
            ->with(['botUser' => function ($q) use ($botId) {
                $q->where('bot_id', $botId);
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $consultation,
        ]);
    }

    /**
     * Обновить статус заявки
     */
    public function updateConsultation(Request $request, string $botId, string $id): JsonResponse
    {
        $consultation = BotConsultation::where('bot_id', $botId)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:new,in_progress,closed',
            'admin_notes' => 'nullable|string',
        ]);

        $consultation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Заявка обновлена',
            'data' => $consultation->fresh(),
        ]);
    }

    /**
     * Получить настройки бота
     */
    public function getSettings(string $botId): JsonResponse
    {
        $bot = Bot::findOrFail($botId);

        return response()->json([
            'success' => true,
            'data' => [
                'required_channel_id' => $bot->required_channel_id,
                'required_channel_username' => $bot->required_channel_username,
                'admin_telegram_ids' => $bot->admin_telegram_ids ?? [],
                'yandex_maps_url' => $bot->yandex_maps_url,
                'welcome_message' => $bot->welcome_message,
                'settings' => $bot->settings ?? [],
            ],
        ]);
    }

    /**
     * Обновить настройки бота
     */
    public function updateSettings(UpdateBotSettingsRequest $request, string $botId): JsonResponse
    {
        $bot = Bot::findOrFail($botId);

        $validated = $request->validated();

        // Обновляем основные поля
        $bot->update([
            'required_channel_id' => $validated['required_channel_id'] ?? $bot->required_channel_id,
            'required_channel_username' => $validated['required_channel_username'] ?? $bot->required_channel_username,
            'admin_telegram_ids' => $validated['admin_telegram_ids'] ?? $bot->admin_telegram_ids,
            'yandex_maps_url' => $validated['yandex_maps_url'] ?? $bot->yandex_maps_url,
            'welcome_message' => $validated['welcome_message'] ?? $bot->welcome_message,
        ]);

        // Обновляем settings JSON
        $settings = $bot->settings ?? [];
        if (isset($validated['settings'])) {
            if (isset($validated['settings']['messages'])) {
                $settings['messages'] = array_merge_recursive($settings['messages'] ?? [], $validated['settings']['messages']);
            }
            if (isset($validated['settings']['other_settings'])) {
                $settings['other_settings'] = array_merge($settings['other_settings'] ?? [], $validated['settings']['other_settings']);
            }
        }
        $bot->settings = $settings;
        $bot->save();

        return response()->json([
            'success' => true,
            'message' => 'Настройки сохранены',
            'data' => $bot->fresh(),
        ]);
    }

    /**
     * Получить список категорий материалов
     */
    public function getMaterialCategories(string $botId): JsonResponse
    {
        $categories = BotMaterialCategory::where('bot_id', $botId)
            ->orderBy('order_index', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Создать категорию материалов
     */
    public function storeMaterialCategory(StoreMaterialCategoryRequest $request, string $botId): JsonResponse
    {
        $category = BotMaterialCategory::create([
            'bot_id' => $botId,
            'name' => $request->name,
            'description' => $request->description,
            'order_index' => $request->order_index ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Категория создана',
            'data' => $category,
        ], 201);
    }

    /**
     * Обновить категорию материалов
     */
    public function updateMaterialCategory(Request $request, string $botId, string $id): JsonResponse
    {
        $category = BotMaterialCategory::where('bot_id', $botId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Категория обновлена',
            'data' => $category->fresh(),
        ]);
    }

    /**
     * Удалить категорию материалов
     */
    public function destroyMaterialCategory(string $botId, string $id): JsonResponse
    {
        $category = BotMaterialCategory::where('bot_id', $botId)->findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Категория удалена',
        ]);
    }

    /**
     * Получить список материалов
     */
    public function getMaterials(Request $request, string $botId): JsonResponse
    {
        $query = BotMaterial::whereHas('category', function ($q) use ($botId) {
            $q->where('bot_id', $botId);
        })->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $materials = $query->orderBy('order_index', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $materials,
        ]);
    }

    /**
     * Создать материал
     */
    public function storeMaterial(StoreMaterialRequest $request, string $botId): JsonResponse
    {
        $category = BotMaterialCategory::where('bot_id', $botId)
            ->findOrFail($request->category_id);

        $data = [
            'category_id' => $category->id,
            'title' => $request->title,
            'description' => $request->description,
            'file_type' => $request->file_type,
            'order_index' => $request->order_index ?? 0,
            'is_active' => $request->is_active ?? true,
        ];

        // Обработка файла в зависимости от типа
        if ($request->file_type === 'file') {
            if ($request->hasFile('file')) {
                // Загружаем новый файл
                $file = $request->file('file');
                $media = $this->saveFileToMediaLibrary($file);
                $data['media_id'] = $media->id;
            } elseif ($request->has('media_id')) {
                // Используем существующий файл из медиа-библиотеки
                $data['media_id'] = $request->media_id;
            }
        } elseif ($request->file_type === 'url') {
            $data['file_url'] = $request->file_url;
        } elseif ($request->file_type === 'telegram_file_id') {
            $data['file_id'] = $request->file_id;
        }

        $material = BotMaterial::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Материал создан',
            'data' => $material->load('category', 'media'),
        ], 201);
    }

    /**
     * Обновить материал
     */
    public function updateMaterial(UpdateMaterialRequest $request, string $botId, string $id): JsonResponse
    {
        $material = BotMaterial::whereHas('category', function ($q) use ($botId) {
            $q->where('bot_id', $botId);
        })->findOrFail($id);

        $data = $request->only(['title', 'description', 'file_type', 'order_index', 'is_active']);

        // Обновление файла
        if ($request->has('file_type')) {
            if ($request->file_type === 'file') {
                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $media = $this->saveFileToMediaLibrary($file);
                    $data['media_id'] = $media->id;
                    $data['file_path'] = null;
                    $data['file_url'] = null;
                    $data['file_id'] = null;
                } elseif ($request->has('media_id')) {
                    $data['media_id'] = $request->media_id;
                    $data['file_path'] = null;
                    $data['file_url'] = null;
                    $data['file_id'] = null;
                }
            } elseif ($request->file_type === 'url') {
                $data['file_url'] = $request->file_url;
                $data['media_id'] = null;
                $data['file_path'] = null;
                $data['file_id'] = null;
            } elseif ($request->file_type === 'telegram_file_id') {
                $data['file_id'] = $request->file_id;
                $data['media_id'] = null;
                $data['file_path'] = null;
                $data['file_url'] = null;
            }
        }

        $material->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Материал обновлен',
            'data' => $material->fresh()->load('category', 'media'),
        ]);
    }

    /**
     * Удалить материал
     */
    public function destroyMaterial(string $botId, string $id): JsonResponse
    {
        $material = BotMaterial::whereHas('category', function ($q) use ($botId) {
            $q->where('bot_id', $botId);
        })->findOrFail($id);

        $material->delete();

        return response()->json([
            'success' => true,
            'message' => 'Материал удален',
        ]);
    }

    /**
     * Получить статистику бота
     */
    public function getStatistics(string $botId): JsonResponse
    {
        $bot = Bot::findOrFail($botId);

        $totalUsers = BotUser::where('bot_id', $botId)->count();
        $activeUsers30d = BotUser::where('bot_id', $botId)
            ->where('last_interaction_at', '>=', now()->subDays(30))
            ->count();

        $totalConsultations = BotConsultation::where('bot_id', $botId)->count();
        $consultationsByStatus = BotConsultation::where('bot_id', $botId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $materialsDownloads = BotMaterial::whereHas('category', function ($q) use ($botId) {
            $q->where('bot_id', $botId);
        })->sum('download_count');

        $popularMaterials = BotMaterial::whereHas('category', function ($q) use ($botId) {
            $q->where('bot_id', $botId);
        })
            ->orderBy('download_count', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'download_count']);

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'active_users_30d' => $activeUsers30d,
                'total_consultations' => $totalConsultations,
                'consultations_by_status' => $consultationsByStatus,
                'materials_downloads' => $materialsDownloads,
                'popular_materials' => $popularMaterials,
            ],
        ]);
    }

    /**
     * Получить список пользователей
     */
    public function getUsers(Request $request, string $botId): JsonResponse
    {
        $query = BotUser::where('bot_id', $botId);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 20), 100);
        $users = $query->orderBy('last_interaction_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Сохранить файл в медиа-библиотеку
     */
    protected function saveFileToMediaLibrary($file): Media
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        $fileName = uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = 'upload/bot_materials';
        $fullPath = public_path($uploadPath);

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $file->move($fullPath, $fileName);
        $relativePath = $uploadPath . '/' . $fileName;

        // Определяем тип файла
        $type = str_starts_with($mimeType, 'image/') ? 'photo' : 'document';

        // Получаем размеры изображения
        $width = null;
        $height = null;
        if ($type === 'photo') {
            $imagePath = public_path($relativePath);
            $imageInfo = @getimagesize($imagePath);
            if ($imageInfo !== false) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        }

        return Media::create([
            'name' => $fileName,
            'original_name' => $originalName,
            'extension' => $extension,
            'disk' => $uploadPath,
            'width' => $width,
            'height' => $height,
            'type' => $type,
            'size' => $fileSize,
            'user_id' => auth()->check() ? auth()->id() : null,
            'temporary' => false,
            'metadata' => json_encode([
                'path' => $relativePath,
                'mime_type' => $mimeType,
            ]),
        ]);
    }
}
