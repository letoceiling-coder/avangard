<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VillageResource extends JsonResource
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
            'prices' => VillagePriceResource::collection($this->whenLoaded('prices')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'main_image' => new ImageResource($this->whenLoaded('mainImage')),
            
            // Статистика
            'stats' => [
                'plots_count' => $this->plots_count,
                'view_plots_count' => $this->view_plots_count,
            ],
            
            // Расстояния
            'distance' => $this->distance,
            
            // Сроки и старт продаж
            'deadline' => $this->deadline,
            'deadline_date' => $this->deadline_date?->toIso8601String(),
            'sales_start' => $this->sales_start,
            'sales_start_date' => $this->sales_start_date?->toIso8601String(),
            
            // Комиссия
            'reward_label' => $this->reward_label,
            
            // Флаги
            'is_new_village' => $this->is_new_village,
            'is_active' => $this->is_active,
            
            // Источник данных
            'data_source' => $this->data_source,
            'parsed_at' => $this->parsed_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            
            // Метаданные
            'metadata' => $this->metadata,
            'property_types' => $this->property_types,
            
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

