<?php

namespace App\Http\Requests\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payment'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $paymentId = $this->route('payment')->id ?? null;
        $ownershipId = request()->input('current_ownership_id');
        if (!$ownershipId) {
            return [];
        }

        return [
            'invoice_id' => [
                'sometimes',
                'integer',
                Rule::exists('invoices', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            // ownership_id cannot be changed via update (taken from middleware scope)
            'method' => ['sometimes', 'string', 'max:50', Rule::in(['cash', 'bank_transfer', 'check', 'other'])],
            'transaction_id' => ['nullable', 'string', 'max:255', Rule::unique('payments', 'transaction_id')->ignore($paymentId)],
            'amount' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['nullable', 'string', 'max:3'],
            'status' => ['nullable', 'string', 'max:50', Rule::in(['pending', 'paid', 'unpaid'])],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'invoice_id' => __('messages.attributes.invoice_id'),
            'method' => __('messages.attributes.payment_method'),
            'transaction_id' => __('messages.attributes.transaction_id'),
            'amount' => __('messages.attributes.amount'),
            'currency' => __('messages.attributes.currency'),
            'status' => __('messages.attributes.status'),
            'paid_at' => __('messages.attributes.payment_date'),
            'notes' => __('messages.attributes.notes'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'invoice_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.invoice_id')]),
            'method.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.payment_method')]),
            'transaction_id.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.transaction_id')]),
            'amount.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.amount')]),
            'paid_at.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.payment_date')]),
            'status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.status')]),
        ];
    }
}

