<?php

namespace App\Http\Resources\V1\Ownership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'number' => $this->number,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'area' => $this->area,
            'price_monthly' => $this->price_monthly,
            'price_quarterly' => $this->price_quarterly,
            'price_yearly' => $this->price_yearly,
            'status' => $this->status,
            'active' => $this->active,
            'ownership' => $this->whenLoaded('ownership', function () {
                return [
                    'uuid' => $this->ownership->uuid,
                    'name' => $this->ownership->name,
                ];
            }),
            'building' => $this->whenLoaded('building', function () {
                return [
                    'uuid' => $this->building->uuid,
                    'name' => $this->building->name,
                    'code' => $this->building->code,
                ];
            }),
            'floor' => $this->whenLoaded('floor', function () {
                return [
                    'id' => $this->floor->id,
                    'number' => $this->floor->number,
                    'name' => $this->floor->name,
                ];
            }),
            'specifications' => $this->whenLoaded('specifications', function () {
                return UnitSpecificationResource::collection($this->specifications);
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
