<?php

namespace App\Http\Resources\V1\Ownership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
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
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'description' => $this->description,
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'floors_count' => $this->floors,
            'year' => $this->year,
            'active' => $this->active,
            'ownership' => $this->when(
                $this->relationLoaded('ownership') && $this->ownership !== null,
                function () {
                    return [
                        'uuid' => $this->ownership->uuid,
                        'name' => $this->ownership->name,
                    ];
                }
            ),
            'portfolio' => $this->when(
                $this->relationLoaded('portfolio') && $this->portfolio !== null,
                function () {
                    return [
                        'uuid' => $this->portfolio->uuid,
                        'name' => $this->portfolio->name,
                        'code' => $this->portfolio->code,
                    ];
                }
            ),
            'parent' => $this->when(
                $this->relationLoaded('parent') && $this->parent !== null,
                function () {
                    return [
                        'uuid' => $this->parent->uuid,
                        'name' => $this->parent->name,
                        'code' => $this->parent->code,
                    ];
                }
            ),
            'children' => $this->whenLoaded('children', function () {
                return BuildingResource::collection($this->children);
            }),
            'floors' => $this->whenLoaded('buildingFloors', function () {
                return BuildingFloorResource::collection($this->buildingFloors);
            }),
            'floors_count' => $this->when(isset($this->building_floors_count), $this->building_floors_count),
            'children_count' => $this->when(isset($this->children_count), $this->children_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
