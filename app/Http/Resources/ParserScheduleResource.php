<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParserScheduleResource extends JsonResource
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
            'object_type' => $this->object_type,
            'object_type_name' => $this->object_type_name,
            'city_ids' => $this->city_ids,
            'time_from' => $this->time_from,
            'time_to' => $this->time_to,
            'days_of_week' => $this->days_of_week,
            'days_of_week_string' => $this->days_of_week_string,
            'is_active' => $this->is_active,
            'check_images' => $this->check_images,
            'force_update' => $this->force_update,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'skip_errors' => $this->skip_errors,
            'last_run_at' => $this->last_run_at?->toIso8601String(),
            'next_run_at' => $this->next_run_at?->toIso8601String(),
            'last_run_total' => $this->last_run_total,
            'last_run_created' => $this->last_run_created,
            'last_run_updated' => $this->last_run_updated,
            'last_run_errors' => $this->last_run_errors,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
