<?php

namespace App\Http\Resources\V1\Ownership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'description' => $this->description,
            'area' => $this->area,
            'active' => $this->active,
            'ownership' => $this->whenLoaded('ownership', function () {
                return [
                    'uuid' => $this->ownership->uuid,
                    'name' => $this->ownership->name,
                ];
            }),
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'uuid' => $this->parent->uuid,
                    'name' => $this->parent->name,
                    'code' => $this->parent->code,
                ];
            }),
            'children' => $this->whenLoaded('children', function () {
                return PortfolioResource::collection($this->children);
            }),
            'locations' => $this->whenLoaded('locations', function () {
                return PortfolioLocationResource::collection($this->locations);
            }),
            'buildings' => $this->whenLoaded('buildings', function () {
                return BuildingResource::collection($this->buildings);
            }),
            'buildings_count' => $this->when(isset($this->buildings_count), $this->buildings_count),
            'children_count' => $this->when(isset($this->children_count), $this->children_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
