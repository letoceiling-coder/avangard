<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters\VillageFilter;
use App\Http\Requests\StoreVillageRequest;
use App\Http\Requests\UpdateVillageRequest;
use App\Http\Resources\VillageResource;
use App\Models\Trend\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VillageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $villages = Village::query()
                ->with(['city', 'builder', 'mainImage'])
                ->filter(new VillageFilter($request->all()))
                ->paginate($perPage);
            
            return VillageResource::collection($villages);
            
        } catch (\Exception $e) {
            Log::error('Error fetching villages list', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка поселков',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVillageRequest $request)
    {
        try {
            $village = Village::create($request->validated());
            
            // Логирование источника данных
            $village->dataSources()->create([
                'source_type' => $request->data_source ?? 'manual',
                'source_name' => $request->data_source === 'parser' ? 'TrendAgent API' : 'Admin Panel',
                'user_id' => Auth::id(),
                'processed_at' => now(),
            ]);
            
            $village->load(['city', 'builder', 'mainImage']);
            
            return new VillageResource($village);
            
        } catch (\Exception $e) {
            Log::error('Error creating village', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при создании поселка',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Village $village)
    {
        try {
            $village->load([
                'city',
                'builder',
                'prices',
                'images',
                'mainImage'
            ]);
            
            return new VillageResource($village);
            
        } catch (\Exception $e) {
            Log::error('Error fetching village', [
                'village_id' => $village->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении поселка',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVillageRequest $request, Village $village)
    {
        try {
            $village->update($request->validated());
            
            // Логирование обновления
            $village->dataSources()->create([
                'source_type' => $village->data_source,
                'source_name' => 'Admin Panel Update',
                'user_id' => Auth::id(),
                'processed_at' => now(),
                'metadata' => ['updated_fields' => array_keys($request->validated())],
            ]);
            
            $village->load(['city', 'builder', 'mainImage']);
            
            return new VillageResource($village);
            
        } catch (\Exception $e) {
            Log::error('Error updating village', [
                'village_id' => $village->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при обновлении поселка',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Village $village)
    {
        try {
            $village->delete(); // Soft delete
            
            return response()->json([
                'message' => 'Поселок успешно удален',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting village', [
                'village_id' => $village->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при удалении поселка',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
    
    /**
     * Получить список устаревших поселков
     */
    public function outdated(Request $request)
    {
        try {
            $days = $request->get('days', 7);
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $villages = Village::query()
                ->outdated($days)
                ->with(['city', 'builder', 'mainImage'])
                ->paginate($perPage);
            
            return VillageResource::collection($villages);
            
        } catch (\Exception $e) {
            Log::error('Error fetching outdated villages', [
                'error' => $e->getMessage(),
                'days' => $request->get('days', 7),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка устаревших поселков',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}
