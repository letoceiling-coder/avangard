<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters\ParkingFilter;
use App\Http\Requests\StoreParkingRequest;
use App\Http\Requests\UpdateParkingRequest;
use App\Http\Resources\ParkingResource;
use App\Models\Trend\Parking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ParkingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100);
        
        $parkings = Parking::query()
            ->with(['city', 'district', 'location', 'builder', 'block', 'subways.subwayLine', 'mainImage'])
            ->filter(new ParkingFilter($request->all()))
            ->paginate($perPage);
        
        return ParkingResource::collection($parkings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreParkingRequest $request)
    {
        try {
            $parking = Parking::create($request->validated());
            
            // Синхронизация связей
            if ($request->has('subway_ids')) {
                $syncData = [];
                foreach ($request->subway_ids as $subwayId) {
                    $syncData[$subwayId] = [
                        'distance_time' => null,
                        'distance_type_id' => 1,
                        'priority' => 500,
                    ];
                }
                $parking->subways()->sync($syncData);
            }
            
            // Логирование источника данных
            $parking->dataSources()->create([
                'source_type' => $request->data_source ?? 'manual',
                'source_name' => $request->data_source === 'parser' ? 'TrendAgent API' : 'Admin Panel',
                'user_id' => Auth::id(),
                'processed_at' => now(),
            ]);
            
            $parking->load(['city', 'builder', 'mainImage']);
            
            return new ParkingResource($parking);
            
        } catch (\Exception $e) {
            Log::error('Error creating parking', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при создании парковочного места',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Parking $parking)
    {
        $parking->load([
            'city',
            'district',
            'location',
            'builder',
            'block',
            'subways.subwayLine',
            'subways.city',
            'images',
            'mainImage'
        ]);
        
        return new ParkingResource($parking);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateParkingRequest $request, Parking $parking)
    {
        try {
            $parking->update($request->validated());
            
            if ($request->has('subway_ids')) {
                $syncData = [];
                foreach ($request->subway_ids as $subwayId) {
                    $syncData[$subwayId] = [
                        'distance_time' => null,
                        'distance_type_id' => 1,
                        'priority' => 500,
                    ];
                }
                $parking->subways()->sync($syncData);
            }
            
            $parking->dataSources()->create([
                'source_type' => $parking->data_source,
                'source_name' => 'Admin Panel Update',
                'user_id' => Auth::id(),
                'processed_at' => now(),
            ]);
            
            $parking->load(['city', 'builder', 'mainImage']);
            
            return new ParkingResource($parking);
            
        } catch (\Exception $e) {
            Log::error('Error updating parking', [
                'parking_id' => $parking->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при обновлении парковочного места',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Parking $parking)
    {
        try {
            $parking->delete();
            
            return response()->json([
                'message' => 'Парковочное место успешно удалено',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting parking', [
                'parking_id' => $parking->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при удалении парковочного места',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

