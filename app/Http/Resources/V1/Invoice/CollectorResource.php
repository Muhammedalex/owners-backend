<?php

namespace App\Http\Resources\V1\Invoice;

use App\Http\Resources\V1\Tenant\TenantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectorResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'is_collector' => $this->hasRole('Collector'),
            'active' => $this->active,
            'assigned_tenants' => $this->when(
                $this->relationLoaded('assignedTenants'),
                function () {
                    return TenantResource::collection($this->assignedTenants);
                }
            ),
            'assigned_tenants_count' => $this->when(
                $this->relationLoaded('assignedTenants'),
                fn() => $this->assignedTenants->count()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
