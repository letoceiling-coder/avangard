<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommercialPremiseResource extends JsonResource
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
            'commercial_block' => new CommercialBlockResource($this->whenLoaded('commercialBlock')),
            'city' => new CityResource($this->whenLoaded('city')),
            'builder' => new BuilderResource($this->whenLoaded('builder')),
            'district' => new RegionResource($this->whenLoaded('district')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'main_image' => new ImageResource($this->whenLoaded('mainImage')),
            
            // Координаты
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            
            // Цены
            'price' => $this->price,
            'price_per_sqm' => $this->price_per_sqm,
            'price_unit' => $this->price_unit,
            
            // Площадь
            'area' => $this->area,
            
            // Характеристики
            'premise_type' => $this->premise_type,
            'property_types' => $this->property_types,
            'building_type' => $this->building_type,
            'floor' => $this->floor,
            'ceiling_height' => $this->ceiling_height,
            
            // Статусы
            'status' => $this->status,
            'is_active' => $this->is_active,
            'is_booked' => $this->is_booked,
            'is_sold' => $this->is_sold,
            
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
