<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'region_id' => $this->region_id, // ID региона, к которому принадлежит город
            'guid' => $this->guid,
            'name' => $this->name,
            'crm_id' => $this->crm_id,
            'external_id' => $this->external_id,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'region' => new RegionResource($this->whenLoaded('region')), // Регион города
            'regions' => RegionResource::collection($this->whenLoaded('regions')), // Для обратной совместимости
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

