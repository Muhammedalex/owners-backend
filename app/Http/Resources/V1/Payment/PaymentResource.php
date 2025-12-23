<?php

namespace App\Http\Resources\V1\Payment;

use App\Http\Resources\V1\Invoice\InvoiceResource;
use App\Http\Resources\V1\Ownership\OwnershipResource;
use App\Http\Resources\V1\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'invoice' => $this->whenLoaded('invoice', function () {
                return new InvoiceResource($this->invoice);
            }),
            'ownership' => $this->whenLoaded('ownership', function () {
                return new OwnershipResource($this->ownership);
            }),
            'method' => $this->method,
            'method_label' => __("messages.payment_methods.{$this->method}") ?: $this->method,
            'transaction_id' => $this->transaction_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'is_paid' => $this->isPaid(),
            'is_pending' => $this->isPending(),
            'is_unpaid' => $this->isUnpaid(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'confirmed_by' => $this->whenLoaded('confirmedBy', function () {
                return new UserResource($this->confirmedBy);
            }),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

