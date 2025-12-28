<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Http\Resources\RegionResource;
use App\Models\Trend\City;
use App\Models\Trend\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    /**
     * Получить список регионов с городами (дерево: Регионы → Города)
     */
    public function index(Request $request)
    {
        try {
            // Получаем регионы (области) с их городами
            $regions = Region::whereNull('city_id') // Только корневые регионы (области)
                ->with(['cities' => function ($query) {
                    $query->orderBy('sort_order')->orderBy('name');
                }])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'data' => RegionResource::collection($regions),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching regions with cities', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Ошибка при получении списка регионов и городов',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Обновить статус активности города
     */
    public function updateCity(Request $request, City $city)
    {
        try {
            $request->validate([
                'is_active' => 'boolean',
            ]);

            $city->update($request->only('is_active'));

            $city->load('region');

            return response()->json([
                'message' => 'Статус города обновлен',
                'data' => new CityResource($city),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating city', [
                'city_id' => $city->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка при обновлении города',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Обновить статус активности региона
     * @deprecated Используйте bulkUpdateRegions для массового обновления
     */
    public function updateRegion(Request $request, Region $region)
    {
        try {
            $request->validate([
                'is_active' => 'boolean',
            ]);

            $region->update($request->only('is_active'));

            $region->load('cities');

            return response()->json([
                'message' => 'Статус региона обновлен',
                'data' => new RegionResource($region),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating region', [
                'region_id' => $region->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка при обновлении региона',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Массовое обновление статусов городов
     */
    public function bulkUpdateCities(Request $request)
    {
        try {
            $request->validate([
                'cities' => 'required|array',
                'cities.*.id' => 'required|exists:cities,id',
                'cities.*.is_active' => 'required|boolean',
            ]);

            foreach ($request->cities as $cityData) {
                City::where('id', $cityData['id'])
                    ->update(['is_active' => $cityData['is_active']]);
            }

            return response()->json([
                'message' => 'Статусы городов обновлены',
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk updating cities', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка при массовом обновлении городов',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Массовое обновление статусов регионов
     */
    public function bulkUpdateRegions(Request $request)
    {
        try {
            $request->validate([
                'regions' => 'required|array',
                'regions.*.id' => 'required|exists:regions,id',
                'regions.*.is_active' => 'required|boolean',
            ]);

            foreach ($request->regions as $regionData) {
                Region::where('id', $regionData['id'])
                    ->update(['is_active' => $regionData['is_active']]);
            }

            return response()->json([
                'message' => 'Статусы регионов обновлены',
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk updating regions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка при массовом обновлении регионов',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}
