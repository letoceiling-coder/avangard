<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceHistoryResource extends JsonResource
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
            'priceable_type' => $this->priceable_type,
            'priceable_id' => $this->priceable_id,
            'price_type' => $this->price_type,
            'old_price' => $this->old_price,
            'new_price' => $this->new_price,
            'old_price_formatted' => $this->formatted_old_price,
            'new_price_formatted' => $this->formatted_new_price,
            'change_amount' => $this->change_amount,
            'change_amount_formatted' => $this->formatted_change_amount,
            'change_percent' => $this->change_percent ? (float) $this->change_percent : null,
            'source' => $this->source,
            'changed_at' => $this->changed_at?->toIso8601String(),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'priceable' => $this->when($this->relationLoaded('priceable'), function () {
                // Возвращаем базовую информацию об объекте, если загружена связь
                return [
                    'id' => $this->priceable->id ?? null,
                    'name' => $this->priceable->name ?? $this->priceable->guid ?? null,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
