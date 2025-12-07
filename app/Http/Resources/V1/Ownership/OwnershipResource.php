<?php

namespace App\Http\Resources\V1\Ownership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnershipResource extends JsonResource
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
            'legal' => $this->legal,
            'type' => $this->type,
            'ownership_type' => $this->ownership_type,
            'registration' => $this->registration,
            'tax_id' => $this->tax_id,
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
            'email' => $this->email,
            'phone' => $this->phone,
            'active' => $this->active,
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'uuid' => $this->createdBy->uuid,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'board_members' => $this->whenLoaded('boardMembers', function () {
                return OwnershipBoardMemberResource::collection($this->boardMembers);
            }),
            'user_mappings' => $this->whenLoaded('userMappings', function () {
                return UserOwnershipMappingResource::collection($this->userMappings);
            }),
            'portfolios' => $this->whenLoaded('portfolios', function () {
                return PortfolioResource::collection($this->portfolios);
            }),
            'portfolios_count' => $this->when(isset($this->portfolios_count), $this->portfolios_count),
            'buildings' => $this->whenLoaded('buildings', function () {
                return BuildingResource::collection($this->buildings);
            }),
            'buildings_count' => $this->when(isset($this->buildings_count), $this->buildings_count),
            'units' => $this->whenLoaded('units', function () {
                return UnitResource::collection($this->units);
            }),
            'units_count' => $this->when(isset($this->units_count), $this->units_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
