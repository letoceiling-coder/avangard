<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParserScheduleRequest;
use App\Http\Requests\UpdateParserScheduleRequest;
use App\Http\Resources\ParserScheduleResource;
use App\Models\ParserSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParserScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $query = ParserSchedule::query();
            
            // Фильтр по типу объекта
            if ($request->has('object_type')) {
                $query->where('object_type', $request->object_type);
            }
            
            // Фильтр по активности
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
            
            $schedules = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return ParserScheduleResource::collection($schedules);
            
        } catch (\Exception $e) {
            Log::error('Error fetching parser schedules list', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка расписаний парсера',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreParserScheduleRequest $request)
    {
        try {
            $schedule = ParserSchedule::create($request->validated());
            
            return new ParserScheduleResource($schedule);
            
        } catch (\Exception $e) {
            Log::error('Error creating parser schedule', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при создании расписания парсера',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ParserSchedule $parserSchedule)
    {
        try {
            return new ParserScheduleResource($parserSchedule);
            
        } catch (\Exception $e) {
            Log::error('Error fetching parser schedule', [
                'schedule_id' => $parserSchedule->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении расписания парсера',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateParserScheduleRequest $request, ParserSchedule $parserSchedule)
    {
        try {
            $parserSchedule->update($request->validated());
            
            return new ParserScheduleResource($parserSchedule);
            
        } catch (\Exception $e) {
            Log::error('Error updating parser schedule', [
                'schedule_id' => $parserSchedule->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при обновлении расписания парсера',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ParserSchedule $parserSchedule)
    {
        try {
            $parserSchedule->delete();
            
            return response()->json([
                'message' => 'Расписание парсера успешно удалено',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting parser schedule', [
                'schedule_id' => $parserSchedule->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при удалении расписания парсера',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}
