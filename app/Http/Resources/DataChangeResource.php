<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataChangeResource extends JsonResource
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
            'changeable_type' => $this->changeable_type,
            'changeable_id' => $this->changeable_id,
            'field_name' => $this->field_name,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'old_value_decoded' => $this->getOldValueDecoded(),
            'new_value_decoded' => $this->getNewValueDecoded(),
            'change_type' => $this->change_type,
            'source' => $this->source,
            'changed_at' => $this->changed_at?->toIso8601String(),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'changeable' => $this->when($this->relationLoaded('changeable'), function () {
                // Возвращаем базовую информацию об объекте, если загружена связь
                return [
                    'id' => $this->changeable->id ?? null,
                    'name' => $this->changeable->name ?? $this->changeable->guid ?? null,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
