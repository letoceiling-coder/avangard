<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
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
            'external_id' => $this->external_id,
            'file_name' => $this->file_name,
            'path' => $this->path,
            'url_thumbnail' => $this->thumbnail_url,
            'url_full' => $this->full_url,
            'alt' => $this->alt,
            'title' => $this->title,
            'description' => $this->description,
            'width' => $this->width,
            'height' => $this->height,
            'size' => $this->size,
            'mime_type' => $this->mime_type,
            'is_main' => $this->is_main,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

