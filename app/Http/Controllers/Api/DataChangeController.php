<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataChangeResource;
use App\Models\DataChange;
use App\Models\Trend\Block;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataChangeController extends Controller
{
    /**
     * Получить список изменений
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $query = DataChange::query()
                ->with(['changeable', 'user'])
                ->orderByDesc('changed_at');
            
            // Фильтры
            if ($request->has('change_type')) {
                $query->where('change_type', $request->change_type);
            }
            
            if ($request->has('source')) {
                $query->where('source', $request->source);
            }
            
            if ($request->has('field_name')) {
                $query->where('field_name', $request->field_name);
            }
            
            if ($request->has('changeable_type')) {
                $query->where('changeable_type', $request->changeable_type);
            }
            
            if ($request->has('changeable_id')) {
                $query->where('changeable_id', $request->changeable_id);
            }
            
            if ($request->has('date_from')) {
                $query->where('changed_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->where('changed_at', '<=', $request->date_to);
            }
            
            $changes = $query->paginate($perPage);
            
            return DataChangeResource::collection($changes);
            
        } catch (\Exception $e) {
            Log::error('Error fetching data changes', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка изменений',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
    
    /**
     * Получить изменения для конкретного объекта
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
            
            $changes = DataChange::where('changeable_type', $modelClass)
                ->where('changeable_id', $id)
                ->with(['user', 'changeable'])
                ->orderByDesc('changed_at')
                ->get();
            
            return response()->json([
                'data' => DataChangeResource::collection($changes),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching object changes', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении изменений объекта',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
    
    /**
     * Получить статистику изменений
     */
    public function statistics(Request $request)
    {
        try {
            $query = DataChange::query();
            
            if ($request->has('date_from')) {
                $query->where('changed_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->where('changed_at', '<=', $request->date_to);
            }
            
            $total = $query->count();
            $byType = $query->selectRaw('change_type, COUNT(*) as count')
                ->groupBy('change_type')
                ->pluck('count', 'change_type')
                ->toArray();
            
            $byField = $query->selectRaw('field_name, COUNT(*) as count')
                ->groupBy('field_name')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'field_name')
                ->toArray();
            
            return response()->json([
                'total' => $total,
                'by_type' => $byType,
                'by_field' => $byField,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching change statistics', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении статистики изменений',
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
