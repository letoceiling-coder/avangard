<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guid' => $this->guid,
            'name' => $this->name,
            'address' => $this->address,
            'external_id' => $this->external_id,
            'crm_id' => $this->crm_id,
            
            // Связи
            'village' => new VillageResource($this->whenLoaded('village')),
            'city' => new CityResource($this->whenLoaded('city')),
            'builder' => new BuilderResource($this->whenLoaded('builder')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'main_image' => new ImageResource($this->whenLoaded('mainImage')),
            
            // Координаты
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            
            // Цены
            'prices' => [
                'min' => $this->min_price,
                'max' => $this->max_price,
            ],
            
            // Площадь
            'area' => [
                'min' => $this->area_min,
                'max' => $this->area_max,
            ],
            
            // Статусы
            'status' => $this->status,
            'is_active' => $this->is_active,
            
            // Источник данных
            'data_source' => $this->data_source,
            'parsed_at' => $this->parsed_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            
            // Метаданные
            'metadata' => $this->metadata,
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
