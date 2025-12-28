<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters\CommercialPremiseFilter;
use App\Http\Requests\StoreCommercialPremiseRequest;
use App\Http\Requests\UpdateCommercialPremiseRequest;
use App\Http\Resources\CommercialPremiseResource;
use App\Models\Trend\CommercialPremise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommercialPremiseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $perPage = min(max($perPage, 1), 100);
            
            $premises = CommercialPremise::query()
                ->with(['commercialBlock', 'city', 'builder', 'district', 'location', 'mainImage'])
                ->filter(new CommercialPremiseFilter($request->all()))
                ->paginate($perPage);
            
            return CommercialPremiseResource::collection($premises);
            
        } catch (\Exception $e) {
            Log::error('Error fetching commercial premises list', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении списка коммерческих помещений',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommercialPremiseRequest $request)
    {
        try {
            $premise = CommercialPremise::create($request->validated());
            
            // Логирование источника данных
            $premise->dataSources()->create([
                'source_type' => $request->data_source ?? 'manual',
                'source_name' => $request->data_source === 'parser' ? 'TrendAgent API' : 'Admin Panel',
                'user_id' => Auth::id(),
                'processed_at' => now(),
            ]);
            
            $premise->load(['commercialBlock', 'city', 'builder', 'district', 'location', 'mainImage']);
            
            return new CommercialPremiseResource($premise);
            
        } catch (\Exception $e) {
            Log::error('Error creating commercial premise', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при создании коммерческого помещения',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CommercialPremise $commercialPremise)
    {
        try {
            $commercialPremise->load([
                'commercialBlock',
                'city',
                'builder',
                'district',
                'location',
                'images',
                'mainImage'
            ]);
            
            return new CommercialPremiseResource($commercialPremise);
            
        } catch (\Exception $e) {
            Log::error('Error fetching commercial premise', [
                'premise_id' => $commercialPremise->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при получении коммерческого помещения',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommercialPremiseRequest $request, CommercialPremise $commercialPremise)
    {
        try {
            $commercialPremise->update($request->validated());
            
            // Логирование обновления
            $commercialPremise->dataSources()->create([
                'source_type' => $commercialPremise->data_source,
                'source_name' => 'Admin Panel Update',
                'user_id' => Auth::id(),
                'processed_at' => now(),
                'metadata' => ['updated_fields' => array_keys($request->validated())],
            ]);
            
            $commercialPremise->load(['commercialBlock', 'city', 'builder', 'district', 'location', 'mainImage']);
            
            return new CommercialPremiseResource($commercialPremise);
            
        } catch (\Exception $e) {
            Log::error('Error updating commercial premise', [
                'premise_id' => $commercialPremise->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при обновлении коммерческого помещения',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommercialPremise $commercialPremise)
    {
        try {
            $commercialPremise->delete(); // Soft delete
            
            return response()->json([
                'message' => 'Коммерческое помещение успешно удалено',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting commercial premise', [
                'premise_id' => $commercialPremise->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при удалении коммерческого помещения',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}
