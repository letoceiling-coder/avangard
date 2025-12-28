<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceHistory;
use App\Models\Trend\Block;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PriceHistoryController extends Controller
{
    /**
     * Получить историю цен
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $query = PriceHistory::query()
                ->with(['priceable', 'user'])
                ->orderByDesc('changed_at');
            
            // Фильтры
            if ($request->has('price_type')) {
                $query->where('price_type', $request->price_type);
            }
            
            if ($request->has('priceable_type')) {
                $query->where('priceable_type', $request->priceable_type);
            }
            
            if ($request->has('priceable_id')) {
                $query->where('priceable_id', $request->priceable_id);
            }
            
            if ($request->has('source')) {
                $query->where('source', $request->source);
            }
            
            if ($request->has('date_from')) {
                $query->where('changed_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->where('changed_at', '<=', $request->date_to);
            }
            
            // Только повышения или снижения
            if ($request->has('change_direction')) {
                if ($request->change_direction === 'increase') {
                    $query->priceIncreases();
                } elseif ($request->change_direction === 'decrease') {
                    $query->priceDecreases();
                }
            }
            
            $history = $query->paginate($perPage);
            
            return response()->json($history);
            
        } catch (\Exception $e) {
            Log::error('Error fetching price history', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении истории цен',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
    
    /**
     * Получить историю цен для конкретного объекта
     */
    public function show($type, $id)
    {
        try {
            $modelClass = $this->getModelClass($type);
            if (!$modelClass) {
                return response()->json([
                    'message' => 'Неподдерживаемый тип объекта',
                ], 400);
            }
            
            $history = PriceHistory::where('priceable_type', $modelClass)
                ->where('priceable_id', $id)
                ->with(['user'])
                ->orderByDesc('changed_at')
                ->get();
            
            return response()->json([
                'data' => $history,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching object price history', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении истории цен объекта',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
    
    /**
     * Получить статистику изменений цен
     */
    public function statistics(Request $request)
    {
        try {
            $query = PriceHistory::query();
            
            if ($request->has('date_from')) {
                $query->where('changed_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->where('changed_at', '<=', $request->date_to);
            }
            
            $total = $query->count();
            $increases = $query->clone()->priceIncreases()->count();
            $decreases = $query->clone()->priceDecreases()->count();
            
            $avgChangePercent = $query->clone()
                ->whereNotNull('change_percent')
                ->avg('change_percent');
            
            $byType = $query->clone()
                ->selectRaw('price_type, COUNT(*) as count')
                ->groupBy('price_type')
                ->pluck('count', 'price_type')
                ->toArray();
            
            return response()->json([
                'total' => $total,
                'increases' => $increases,
                'decreases' => $decreases,
                'avg_change_percent' => round($avgChangePercent, 2),
                'by_type' => $byType,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching price statistics', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении статистики цен',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
    
    /**
     * Получить класс модели по типу
     */
    protected function getModelClass(string $type): ?string
    {
        $types = [
            'block' => Block::class,
            'blocks' => Block::class,
            // Можно добавить другие типы: 'parking', 'village', 'commercial_block'
        ];
        
        return $types[strtolower($type)] ?? null;
    }
}
