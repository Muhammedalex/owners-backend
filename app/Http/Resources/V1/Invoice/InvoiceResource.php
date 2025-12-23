<?php

namespace App\Http\Resources\V1\Invoice;

use App\Http\Resources\V1\Contract\ContractResource;
use App\Http\Resources\V1\Ownership\OwnershipResource;
use App\Http\Resources\V1\Payment\PaymentResource;
use App\Http\Resources\V1\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'contract' => $this->whenLoaded('contract', function () {
                return new ContractResource($this->contract);
            }),
            'ownership' => $this->whenLoaded('ownership', function () {
                return new OwnershipResource($this->ownership);
            }),
            'number' => $this->number,
            'period_start' => $this->period_start->format('Y-m-d'),
            'period_end' => $this->period_end->format('Y-m-d'),
            'due' => $this->due->format('Y-m-d'),
            'amount' => (float) $this->amount,
            'tax' => $this->tax ? (float) $this->tax : null,
            'tax_rate' => $this->tax_rate ? (float) $this->tax_rate : null,
            'tax_from_contract' => (bool) $this->tax_from_contract,
            'is_linked_to_contract' => $this->isLinkedToContract(),
            'is_standalone' => $this->isStandalone(),
            'total' => (float) $this->total,
            'status' => $this->status instanceof \App\Enums\V1\Invoice\InvoiceStatus 
                ? $this->status->value 
                : $this->status,
            'status_label' => $this->status instanceof \App\Enums\V1\Invoice\InvoiceStatus 
                ? $this->status->label() 
                : null,
            'status_color' => $this->status instanceof \App\Enums\V1\Invoice\InvoiceStatus 
                ? $this->status->color() 
                : null,
            'can_be_edited' => $request->user() 
                ? app(\App\Services\V1\Invoice\InvoiceEditRulesService::class)
                    ->canEdit($this->resource, $request->user())
                : false,
            'can_be_deleted' => $request->user()
                ? app(\App\Services\V1\Invoice\InvoiceEditRulesService::class)
                    ->canDelete($this->resource, $request->user())
                : false,
            'is_paid' => $this->isPaid(),
            'is_overdue' => $this->isOverdue(),
            'is_draft' => $this->isDraft(),
            'notes' => $this->notes,
            'generated_by' => $this->whenLoaded('generatedBy', function () {
                return new UserResource($this->generatedBy);
            }),
            'generated_at' => $this->generated_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', function () {
                return InvoiceItemResource::collection($this->items);
            }),
            'items_total' => $this->whenLoaded('items', function () {
                return (float) $this->calculateTotalFromItems();
            }),
            'payments' => $this->whenLoaded('payments', function () {
                return PaymentResource::collection($this->payments);
            }),
            'payments_count' => $this->when(isset($this->payments_count), $this->payments_count),
            'total_paid' => $this->whenLoaded('payments', function () {
                return (float) $this->payments->where('status', 'paid')->sum('amount');
            }, function () {
                // If payments not loaded, calculate from database
                return (float) $this->payments()->where('status', 'paid')->sum('amount');
            }),
            'remaining_amount' => $this->whenLoaded('payments', function () {
                $paid = (float) $this->payments->where('status', 'paid')->sum('amount');
                return max(0, (float) $this->total - $paid);
            }, function () {
                // If payments not loaded, calculate from database
                $paid = (float) $this->payments()->where('status', 'paid')->sum('amount');
                return max(0, (float) $this->total - $paid);
            }),
            'allow_partial_payment' => app(\App\Services\V1\Invoice\InvoiceSettingService::class)
                ->allowPartialPayment($this->ownership_id),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

