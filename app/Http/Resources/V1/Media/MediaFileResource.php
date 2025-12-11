<?php

namespace App\Http\Resources\V1\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaFileResource extends JsonResource
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
            'type' => $this->type,
            'url' => $this->url,
            'name' => $this->name,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'mime' => $this->mime,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'public' => $this->public,
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'uploaded_by' => $this->whenLoaded('uploadedBy', function () {
                return [
                    'id' => $this->uploadedBy->id,
                    'name' => $this->uploadedBy->name,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

