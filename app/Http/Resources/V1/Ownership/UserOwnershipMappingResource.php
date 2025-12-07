<?php

namespace App\Http\Resources\V1\Ownership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserOwnershipMappingResource extends JsonResource
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
            'default' => $this->default,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'uuid' => $this->user->uuid,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'ownership' => $this->whenLoaded('ownership', function () {
                return [
                    'uuid' => $this->ownership->uuid,
                    'name' => $this->ownership->name,
                    'type' => $this->ownership->type,
                    'ownership_type' => $this->ownership->ownership_type,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
