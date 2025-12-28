<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubwayLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'line_number' => $this->line_number,
            'external_id' => $this->external_id,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'city' => new CityResource($this->whenLoaded('city')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

