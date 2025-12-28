<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubwayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guid' => $this->guid,
            'name' => $this->name,
            'crm_id' => $this->crm_id,
            'external_id' => $this->external_id,
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'subway_line' => new SubwayLineResource($this->whenLoaded('subwayLine')),
            'city' => new CityResource($this->whenLoaded('city')),
            'distance' => $this->when($this->pivot, [
                'time' => $this->pivot->distance_time ?? null,
                'type_id' => $this->pivot->distance_type_id ?? null,
                'type' => $this->pivot->distance_type ?? null,
                'priority' => $this->pivot->priority ?? null,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

