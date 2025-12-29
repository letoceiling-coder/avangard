<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockResource extends JsonResource
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
            'crm_id' => $this->crm_id,
            'external_id' => $this->external_id,
            
            // Связи
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'builder' => new BuilderResource($this->whenLoaded('builder')),
            'subways' => SubwayResource::collection($this->whenLoaded('subways')),
            'prices' => BlockPriceResource::collection($this->whenLoaded('prices')),
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
                'min_formatted' => $this->formatted_min_price,
                'max_formatted' => $this->formatted_max_price,
            ],
            
            // Статистика
            'stats' => [
                'apartments_count' => $this->apartments_count,
                'view_apartments_count' => $this->view_apartments_count,
                'exclusive_apartments_count' => $this->exclusive_apartments_count,
            ],
            
            // Статусы
            'status' => $this->status,
            'is_suite' => $this->is_suite,
            'is_exclusive' => $this->is_exclusive,
            'is_marked' => $this->is_marked,
            'is_active' => $this->is_active,
            
            // Сроки
            'deadline' => $this->deadline,
            'deadline_date' => $this->deadline_date?->toIso8601String(),
            'finishing' => $this->finishing,
            
            // Источник данных
            'data_source' => $this->data_source,
            'parsed_at' => $this->formatDateTime($this->parsed_at),
            'last_synced_at' => $this->formatDateTime($this->last_synced_at),
            
            // Метаданные
            'metadata' => $this->metadata,
            'advantages' => $this->advantages,
            'payment_types' => $this->payment_types,
            'contract_types' => $this->contract_types,
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
    
    /**
     * Безопасное форматирование даты/времени
     * Обрабатывает как объекты Carbon, так и строки
     *
     * @param mixed $date
     * @return string|null
     */
    private function formatDateTime($date): ?string
    {
        if (is_null($date)) {
            return null;
        }
        
        if ($date instanceof Carbon || $date instanceof \DateTimeInterface) {
            return $date->toIso8601String();
        }
        
        if (is_string($date)) {
            try {
                return Carbon::parse($date)->toIso8601String();
            } catch (\Exception $e) {
                return $date; // Возвращаем исходную строку, если не удалось распарсить
            }
        }
        
        return null;
    }
}

