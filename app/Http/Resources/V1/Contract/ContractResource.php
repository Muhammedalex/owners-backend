<?php

namespace App\Http\Resources\V1\Contract;

use App\Http\Resources\V1\Invoice\InvoiceResource;
use App\Http\Resources\V1\Ownership\OwnershipResource;
use App\Http\Resources\V1\Ownership\UnitResource;
use App\Http\Resources\V1\Tenant\TenantResource;
use App\Http\Resources\V1\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
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
            'unit' => $this->whenLoaded('unit', function () {
                return new UnitResource($this->unit);
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return new TenantResource($this->tenant);
            }),
            'ownership' => $this->whenLoaded('ownership', function () {
                return new OwnershipResource($this->ownership);
            }),
            'number' => $this->number,
            'version' => $this->version,
            'parent' => $this->whenLoaded('parent', function () {
                return new ContractResource($this->parent);
            }),
            'children' => $this->whenLoaded('children', function () {
                return ContractResource::collection($this->children);
            }),
            'ejar_code' => $this->ejar_code,
            'has_ejar_code' => $this->hasEjarCode(),
            'start' => $this->start->format('Y-m-d'),
            'end' => $this->end->format('Y-m-d'),
            'rent' => (float) $this->rent,
            'payment_frequency' => $this->payment_frequency,
            'deposit' => $this->deposit ? (float) $this->deposit : null,
            'deposit_status' => $this->deposit_status,
            'document' => $this->document,
            'signature' => $this->signature,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_draft' => $this->isDraft(),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return new UserResource($this->createdBy);
            }),
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return new UserResource($this->approvedBy);
            }),
            'terms' => $this->whenLoaded('terms', function () {
                return ContractTermResource::collection($this->terms);
            }),
            'invoices' => $this->whenLoaded('invoices', function () {
                return InvoiceResource::collection($this->invoices);
            }),
            'invoices_count' => $this->when(isset($this->invoices_count), $this->invoices_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

