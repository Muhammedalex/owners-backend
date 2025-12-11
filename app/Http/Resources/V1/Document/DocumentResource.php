<?php

namespace App\Http\Resources\V1\Document;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'mime' => $this->mime,
            'public' => $this->public,
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->isExpired(),
            'expires_soon' => $this->expiresSoon(),
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

