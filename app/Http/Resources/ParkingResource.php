<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'block_id' => $this->block_id,
            'block_guid' => $this->block_guid,
            'block_name' => $this->block_name,
            'number' => $this->number,
            'floor' => $this->floor,
            'area' => $this->area,
            
            // Связи
            'city' => new CityResource($this->whenLoaded('city')),
            'district' => new RegionResource($this->whenLoaded('district')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'builder' => new BuilderResource($this->whenLoaded('builder')),
            'block' => new BlockResource($this->whenLoaded('block')),
            'subways' => SubwayResource::collection($this->whenLoaded('subways')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'main_image' => new ImageResource($this->whenLoaded('mainImage')),
            
            // Координаты
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            
            // Типы и статусы
            'parking_type' => $this->parking_type,
            'place_type' => $this->place_type,
            'property_type' => $this->property_type,
            'status' => $this->status,
            'status_label' => $this->status_label,
            
            // Цена и комиссия
            'price' => $this->price,
            'price_formatted' => $this->formatted_price,
            'reward_label' => $this->reward_label,
            
            // Сроки
            'deadline' => $this->deadline,
            'deadline_date' => $this->deadline_date?->toIso8601String(),
            
            // Источник данных
            'data_source' => $this->data_source,
            'parsed_at' => $this->parsed_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            
            // Метаданные
            'metadata' => $this->metadata,
            
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

