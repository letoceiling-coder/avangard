<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Получить все настройки
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');
        
        if ($group) {
            $settings = Setting::where('group', $group)->get();
        } else {
            $settings = Setting::all();
        }

        return response()->json([
            'data' => $settings->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => Setting::castValue($setting->value, $setting->type),
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'group' => $setting->group,
                    'created_at' => $setting->created_at?->toIso8601String(),
                    'updated_at' => $setting->updated_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Получить настройку по ключу
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Настройка не найдена',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => Setting::get($setting->key),
                'type' => $setting->type,
                'description' => $setting->description,
                'group' => $setting->group,
                'created_at' => $setting->created_at?->toIso8601String(),
                'updated_at' => $setting->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Обновить настройку
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'type' => 'sometimes|in:string,integer,boolean,json,float',
            'description' => 'nullable|string',
            'group' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Настройка не найдена',
            ], 404);
        }

        $value = $request->input('value');
        $type = $request->input('type', $setting->type);
        
        // Если тип json, преобразуем значение в JSON
        if ($type === 'json' && !is_string($value)) {
            $value = json_encode($value);
        } elseif ($type !== 'json' && !is_string($value)) {
            $value = (string) $value;
        }

        $setting->update([
            'value' => $value,
            'type' => $type,
            'description' => $request->input('description', $setting->description),
            'group' => $request->input('group', $setting->group),
        ]);

        $setting->refresh();

        return response()->json([
            'message' => 'Настройка обновлена',
            'data' => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => Setting::get($setting->key),
                'type' => $setting->type,
                'description' => $setting->description,
                'group' => $setting->group,
                'created_at' => $setting->created_at?->toIso8601String(),
                'updated_at' => $setting->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Получить настройки группы TrendAgent
     */
    public function trend(): JsonResponse
    {
        $settings = Setting::getByGroup('trend');

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Обновить настройки группы TrendAgent
     */
    public function updateTrend(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        Setting::set('trend.phone', $request->input('phone'), 'string', 'Телефон для авторизации в TrendAgent API', 'trend');
        Setting::set('trend.password', $request->input('password'), 'string', 'Пароль для авторизации в TrendAgent API', 'trend');

        return response()->json([
            'message' => 'Настройки TrendAgent обновлены',
            'data' => Setting::getByGroup('trend'),
        ]);
    }
}
