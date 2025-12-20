<?php

namespace App\Http\Resources\V1\Tenant;

use App\Http\Resources\V1\Auth\UserResource;
use App\Http\Resources\V1\Contract\ContractResource;
use App\Http\Resources\V1\Media\MediaFileResource;
use App\Http\Resources\V1\Ownership\OwnershipResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
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
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'ownership' => $this->whenLoaded('ownership', function () {
                return new OwnershipResource($this->ownership);
            }),
            'national_id' => $this->national_id,
            'id_type' => $this->id_type,
            'id_document' => $this->id_document,
            'id_expiry' => $this->id_expiry?->format('Y-m-d'),
            'id_valid' => $this->hasValidId(),
            'id_expired' => $this->isIdExpired(),
            'id_document_image' => $this->when(
                $this->relationLoaded('mediaFiles'),
                fn() => $this->getMediaFile('tenant_id_document') ? new MediaFileResource($this->getMediaFile('tenant_id_document')) : null
            ),
            'commercial_registration_number' => $this->commercial_registration_number,
            'commercial_registration_expiry' => $this->commercial_registration_expiry?->format('Y-m-d'),
            'commercial_owner_name' => $this->commercial_owner_name,
            'commercial_registration_image' => $this->when(
                $this->relationLoaded('mediaFiles'),
                fn() => $this->getMediaFile('tenant_cr_document') ? new MediaFileResource($this->getMediaFile('tenant_cr_document')) : null
            ),
            'municipality_license_number' => $this->municipality_license_number,
            'municipality_license_image' => $this->when(
                $this->relationLoaded('mediaFiles'),
                fn() => $this->getMediaFile('tenant_municipality_license') ? new MediaFileResource($this->getMediaFile('tenant_municipality_license')) : null
            ),
            'activity_name' => $this->activity_name,
            'activity_type' => $this->activity_type,
            'emergency_name' => $this->emergency_name,
            'emergency_phone' => $this->emergency_phone,
            'emergency_relation' => $this->emergency_relation,
            'employment' => $this->employment,
            'employer' => $this->employer,
            'income' => $this->income ? (float) $this->income : null,
            'rating' => $this->rating,
            'notes' => $this->notes,
            'contracts' => $this->whenLoaded('contracts', function () {
                return ContractResource::collection($this->contracts);
            }),
            'contracts_count' => $this->when(isset($this->contracts_count), $this->contracts_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

