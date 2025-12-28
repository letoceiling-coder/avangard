<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters\BlockFilter;
use App\Http\Requests\StoreBlockRequest;
use App\Http\Requests\UpdateBlockRequest;
use App\Http\Resources\BlockResource;
use App\Models\Trend\Block;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BlockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100); // Ограничиваем от 1 до 100
            
            $blocks = Block::query()
                ->with(['city', 'region', 'location', 'builder', 'subways.subwayLine', 'mainImage'])
                ->filter(new BlockFilter($request->all()))
                ->paginate($perPage);
            
            return BlockResource::collection($blocks);
            
        } catch (\Exception $e) {
            Log::error('Error fetching blocks list', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка блоков',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlockRequest $request)
    {
        try {
            $block = Block::create($request->validated());
            
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
            
            $block->load(['city', 'builder', 'subways.subwayLine', 'mainImage']);
            
            return new BlockResource($block);
            
        } catch (\Exception $e) {
            Log::error('Error creating block', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при создании блока',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Block $block)
    {
        try {
            $block->load([
                'city',
                'region',
                'location',
                'builder',
                'subways.subwayLine',
                'subways.city',
                'prices',
                'images',
                'mainImage'
            ]);
            
            return new BlockResource($block);
            
        } catch (\Exception $e) {
            Log::error('Error fetching block', [
                'block_id' => $block->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении блока',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlockRequest $request, Block $block)
    {
        try {
            $block->update($request->validated());
            
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
            
            // Логирование обновления
            $block->dataSources()->create([
                'source_type' => $block->data_source,
                'source_name' => 'Admin Panel Update',
                'user_id' => Auth::id(),
                'processed_at' => now(),
                'metadata' => ['updated_fields' => array_keys($request->validated())],
            ]);
            
            $block->load(['city', 'builder', 'mainImage']);
            
            return new BlockResource($block);
            
        } catch (\Exception $e) {
            Log::error('Error updating block', [
                'block_id' => $block->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при обновлении блока',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Block $block)
    {
        try {
            $block->delete(); // Soft delete
            
            return response()->json([
                'message' => 'Блок успешно удален',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting block', [
                'block_id' => $block->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при удалении блока',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
    
    /**
     * Получить список устаревших блоков
     */
    public function outdated(Request $request)
    {
        try {
            $days = $request->get('days', 7);
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $blocks = Block::query()
                ->outdated($days)
                ->with(['city', 'region', 'location', 'builder', 'mainImage'])
                ->paginate($perPage);
            
            // Добавляем информацию о количестве дней с последней синхронизации
            $blocks->getCollection()->transform(function ($block) {
                $block->days_since_last_sync = $block->getDaysSinceLastSync();
                return $block;
            });
            
            return BlockResource::collection($blocks);
            
        } catch (\Exception $e) {
            Log::error('Error fetching outdated blocks', [
                'error' => $e->getMessage(),
                'days' => $request->get('days', 7),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка устаревших блоков',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}

