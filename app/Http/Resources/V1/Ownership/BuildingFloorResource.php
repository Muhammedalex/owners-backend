<?php

namespace App\Http\Resources\V1\Ownership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingFloorResource extends JsonResource
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
            'number' => $this->number,
            'name' => $this->name,
            'description' => $this->description,
            'units' => $this->units,
            'active' => $this->active,
            'building' => $this->whenLoaded('building', function () {
                return [
                    'uuid' => $this->building->uuid,
                    'name' => $this->building->name,
                    'code' => $this->building->code,
                ];
            }),
        ];
    }
}
