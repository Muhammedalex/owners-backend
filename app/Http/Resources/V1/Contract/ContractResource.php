<?php

namespace App\Http\Resources\V1\Contract;

use App\Http\Resources\V1\Invoice\InvoiceResource;
use App\Http\Resources\V1\Ownership\OwnershipResource;
use App\Http\Resources\V1\Ownership\UnitResource;
use App\Http\Resources\V1\Tenant\TenantResource;
use App\Http\Resources\V1\Auth\UserResource;
use App\Http\Resources\V1\Document\DocumentResource;
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
            // All units for this contract (many-to-many) with pivot data (rent_amount, notes)
            'units' => $this->whenLoaded('units', function () {
                return $this->units->map(function ($unit) {
                    $unitResource = new UnitResource($unit);
                    return array_merge($unitResource->toArray(request()), [
                        'rent_amount' => $unit->pivot->rent_amount !== null ? (float) $unit->pivot->rent_amount : null,
                        'notes' => $unit->pivot->notes ?? null,
                    ]);
                });
            }),
            'total_area' => $this->whenLoaded('units', function () {
                return (float) $this->units->sum(function ($unit) {
                    return (float) ($unit->area ?? 0);
                });
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
            'base_rent' => $this->base_rent !== null ? (float) $this->base_rent : null,
            'rent_fees' => $this->rent_fees !== null ? (float) $this->rent_fees : null,
            'vat_amount' => $this->vat_amount !== null ? (float) $this->vat_amount : null,
            'total_rent' => $this->total_rent !== null ? (float) $this->total_rent : null,
            'previous_balance' => $this->previous_balance !== null ? (float) $this->previous_balance : null,
            'payment_frequency' => $this->payment_frequency,
            'deposit' => $this->deposit ? (float) $this->deposit : null,
            'deposit_status' => $this->deposit_status,
            'document' => $this->document,
            'signature' => $this->signature,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_draft' => $this->isDraft(),
            'units_count' => $this->when(isset($this->units_count), $this->units_count, function () {
                return $this->whenLoaded('units', function () {
                    return $this->units->count();
                });
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return new UserResource($this->createdBy);
            }),
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return new UserResource($this->approvedBy);
            }),
            'terms' => $this->whenLoaded('terms', function () {
                return ContractTermResource::collection($this->terms);
            }),
            // Ejar PDF document (first document of type 'ejar_pdf')
            'ejar_pdf' => $this->when(
                $this->relationLoaded('documents'),
                function () {
                    $doc = $this->getDocument('ejar_pdf');
                    return $doc ? new DocumentResource($doc) : null;
                }
            ),
            'invoices' => $this->whenLoaded('invoices', function () {
                return InvoiceResource::collection($this->invoices);
            }),
            'invoices_count' => $this->when(isset($this->invoices_count), $this->invoices_count),
            'documents' => $this->whenLoaded('documents', function () {
                return DocumentResource::collection($this->documents);
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

