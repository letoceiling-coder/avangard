<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'src' => $this->src,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function() {
                return new FolderResource($this->parent);
            }),
            'children' => FolderResource::collection($this->whenLoaded('children')),
            'position' => $this->position,
            'count' => $this->filesCount,
            'countFolder' => $this->children->count(),
            'protected' => $this->protected ?? false,
            'is_trash' => $this->is_trash ?? false,
        ];
    }
}
