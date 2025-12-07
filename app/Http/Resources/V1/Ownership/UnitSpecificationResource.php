<?php

namespace App\Http\Resources\V1\Ownership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitSpecificationResource extends JsonResource
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
            'key' => $this->key,
            'value' => $this->value,
            'type' => $this->type,
        ];
    }
}
