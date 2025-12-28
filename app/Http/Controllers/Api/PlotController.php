<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters\PlotFilter;
use App\Http\Requests\StorePlotRequest;
use App\Http\Requests\UpdatePlotRequest;
use App\Http\Resources\PlotResource;
use App\Models\Trend\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PlotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $plots = Plot::query()
                ->with(['village', 'city', 'builder', 'location', 'mainImage'])
                ->filter(new PlotFilter($request->all()))
                ->paginate($perPage);
            
            return PlotResource::collection($plots);
            
        } catch (\Exception $e) {
            Log::error('Error fetching plots list', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка участков',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlotRequest $request)
    {
        try {
            $plot = Plot::create($request->validated());
            
            // Логирование источника данных
            $plot->dataSources()->create([
                'source_type' => $request->data_source ?? 'manual',
                'source_name' => $request->data_source === 'parser' ? 'TrendAgent API' : 'Admin Panel',
                'user_id' => Auth::id(),
                'processed_at' => now(),
            ]);
            
            $plot->load(['village', 'city', 'builder', 'location', 'mainImage']);
            
            return new PlotResource($plot);
            
        } catch (\Exception $e) {
            Log::error('Error creating plot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при создании участка',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Plot $plot)
    {
        try {
            $plot->load([
                'village',
                'city',
                'builder',
                'location',
                'images',
                'mainImage'
            ]);
            
            return new PlotResource($plot);
            
        } catch (\Exception $e) {
            Log::error('Error fetching plot', [
                'plot_id' => $plot->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении участка',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlotRequest $request, Plot $plot)
    {
        try {
            $plot->update($request->validated());
            
            // Логирование обновления
            $plot->dataSources()->create([
                'source_type' => $plot->data_source,
                'source_name' => 'Admin Panel Update',
                'user_id' => Auth::id(),
                'processed_at' => now(),
                'metadata' => ['updated_fields' => array_keys($request->validated())],
            ]);
            
            $plot->load(['village', 'city', 'builder', 'location', 'mainImage']);
            
            return new PlotResource($plot);
            
        } catch (\Exception $e) {
            Log::error('Error updating plot', [
                'plot_id' => $plot->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при обновлении участка',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plot $plot)
    {
        try {
            $plot->delete(); // Soft delete
            
            return response()->json([
                'message' => 'Участок успешно удален',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting plot', [
                'plot_id' => $plot->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при удалении участка',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}
