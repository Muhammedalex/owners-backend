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
            'tax' => (float) $this->tax,
            'tax_rate' => (float) $this->tax_rate,
            'total' => (float) $this->total,
            'status' => $this->status,
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
            }),
            'remaining_amount' => $this->whenLoaded('payments', function () {
                $paid = (float) $this->payments->where('status', 'paid')->sum('amount');
                return max(0, (float) $this->total - $paid);
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

