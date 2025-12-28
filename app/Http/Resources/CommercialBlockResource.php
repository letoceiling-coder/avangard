<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommercialBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guid' => $this->guid,
            'name' => $this->name,
            'address' => $this->address,
            'external_id' => $this->external_id,
            
            // Связи
            'city' => new CityResource($this->whenLoaded('city')),
            'builder' => new BuilderResource($this->whenLoaded('builder')),
            'district' => new RegionResource($this->whenLoaded('district')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'subways' => SubwayResource::collection($this->whenLoaded('subways')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'main_image' => new ImageResource($this->whenLoaded('mainImage')),
            
            // Статистика
            'stats' => [
                'premises_count' => $this->premises_count,
                'booked_premises_count' => $this->booked_premises_count,
            ],
            
            // Флаги
            'is_new_block' => $this->is_new_block,
            'is_active' => $this->is_active,
            
            // Сроки
            'deadlines' => $this->deadlines,
            'deadline_date' => $this->deadline_date?->toIso8601String(),
            'sales_start_at' => $this->sales_start_at,
            
            // Комиссия
            'reward_label' => $this->reward_label,
            
            // Источник данных
            'data_source' => $this->data_source,
            'parsed_at' => $this->parsed_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            
            // Метаданные
            'metadata' => $this->metadata,
            'property_types' => $this->property_types,
            'min_prices' => $this->min_prices,
            
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

