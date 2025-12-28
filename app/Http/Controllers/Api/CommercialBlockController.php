<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters\CommercialBlockFilter;
use App\Http\Requests\StoreCommercialBlockRequest;
use App\Http\Requests\UpdateCommercialBlockRequest;
use App\Http\Resources\CommercialBlockResource;
use App\Models\Trend\CommercialBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommercialBlockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $blocks = CommercialBlock::query()
                ->with(['city', 'builder', 'district', 'location', 'mainImage'])
                ->filter(new CommercialBlockFilter($request->all()))
                ->paginate($perPage);
            
            return CommercialBlockResource::collection($blocks);
            
        } catch (\Exception $e) {
            Log::error('Error fetching commercial blocks list', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка коммерческих объектов',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommercialBlockRequest $request)
    {
        try {
            $block = CommercialBlock::create($request->validated());
            
            // Синхронизация связей
            if ($request->has('subway_ids')) {
                $syncData = [];
                foreach ($request->subway_ids as $subwayId) {
                    $syncData[$subwayId] = [
                        'distance_time' => null,
                        'distance_type_id' => 1,
                        'distance_type' => 'пешком',
                        'priority' => 500,
                    ];
                }
                $block->subways()->sync($syncData);
            }
            
            // Логирование источника данных
            $block->dataSources()->create([
                'source_type' => $request->data_source ?? 'manual',
                'source_name' => $request->data_source === 'parser' ? 'TrendAgent API' : 'Admin Panel',
                'user_id' => Auth::id(),
                'processed_at' => now(),
            ]);
            
            $block->load(['city', 'builder', 'district', 'location', 'subways.subwayLine', 'mainImage']);
            
            return new CommercialBlockResource($block);
            
        } catch (\Exception $e) {
            Log::error('Error creating commercial block', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при создании коммерческого объекта',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CommercialBlock $commercialBlock)
    {
        try {
            $commercialBlock->load([
                'city',
                'builder',
                'district',
                'location',
                'subways.subwayLine',
                'images',
                'mainImage'
            ]);
            
            return new CommercialBlockResource($commercialBlock);
            
        } catch (\Exception $e) {
            Log::error('Error fetching commercial block', [
                'block_id' => $commercialBlock->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении коммерческого объекта',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommercialBlockRequest $request, CommercialBlock $commercialBlock)
    {
        try {
            $commercialBlock->update($request->validated());
            
            // Синхронизация связей
            if ($request->has('subway_ids')) {
                $syncData = [];
                foreach ($request->subway_ids as $subwayId) {
                    $syncData[$subwayId] = [
                        'distance_time' => null,
                        'distance_type_id' => 1,
                        'distance_type' => 'пешком',
                        'priority' => 500,
                    ];
                }
                $commercialBlock->subways()->sync($syncData);
            }
            
            // Логирование обновления
            $commercialBlock->dataSources()->create([
                'source_type' => $commercialBlock->data_source,
                'source_name' => 'Admin Panel Update',
                'user_id' => Auth::id(),
                'processed_at' => now(),
                'metadata' => ['updated_fields' => array_keys($request->validated())],
            ]);
            
            $commercialBlock->load(['city', 'builder', 'district', 'location', 'subways.subwayLine', 'mainImage']);
            
            return new CommercialBlockResource($commercialBlock);
            
        } catch (\Exception $e) {
            Log::error('Error updating commercial block', [
                'block_id' => $commercialBlock->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при обновлении коммерческого объекта',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommercialBlock $commercialBlock)
    {
        try {
            $commercialBlock->delete(); // Soft delete
            
            return response()->json([
                'message' => 'Коммерческий объект успешно удален',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting commercial block', [
                'block_id' => $commercialBlock->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при удалении коммерческого объекта',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}
