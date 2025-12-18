<?php

namespace App\Http\Resources\V1\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantInvitationResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'name' => $this->name,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'accepted_at' => $this->accepted_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'invitation_url' => $this->getInvitationUrl(),
            'is_expired' => $this->isExpired(),
            'is_pending' => $this->isPending(),
            'is_accepted' => $this->isAccepted(),
            'is_cancelled' => $this->isCancelled(),
            'ownership' => $this->whenLoaded('ownership', function () {
                return [
                    'uuid' => $this->ownership->uuid,
                    'name' => $this->ownership->name,
                ];
            }),
            'invited_by' => $this->whenLoaded('invitedBy', function () {
                return [
                    'uuid' => $this->invitedBy->uuid,
                    'name' => $this->invitedBy->first . ' ' . $this->invitedBy->last,
                    'email' => $this->invitedBy->email,
                ];
            }),
            'accepted_by' => $this->whenLoaded('acceptedBy', function () {
                return $this->acceptedBy ? [
                    'uuid' => $this->acceptedBy->uuid,
                    'name' => $this->acceptedBy->first . ' ' . $this->acceptedBy->last,
                    'email' => $this->acceptedBy->email,
                ] : null;
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return $this->tenant ? [
                    'id' => $this->tenant->id,
                    'national_id' => $this->tenant->national_id,
                ] : null;
            }),
            'tenants_count' => $this->when($this->relationLoaded('tenants'), function () {
                return $this->tenants->count();
            }),
            'tenants' => $this->whenLoaded('tenants', function () {
                return $this->tenants->map(function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'user' => [
                            'name' => $tenant->user->name ?? null,
                            'email' => $tenant->user->email ?? null,
                        ],
                        'national_id' => $tenant->national_id,
                        'created_at' => $tenant->created_at->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

