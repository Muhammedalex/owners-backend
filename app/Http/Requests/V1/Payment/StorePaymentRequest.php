<?php

namespace App\Http\Requests\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\Payment\Payment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ownershipId = request()->input('current_ownership_id');
        if (!$ownershipId) {
            return [];
        }

        return [
            'invoice_id' => [
                'required',
                'integer',
                Rule::exists('invoices', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            // ownership_id is taken from middleware (current_ownership_id), not from request
            'method' => ['required', 'string', 'max:50', Rule::in(['cash', 'bank_transfer', 'check', 'other'])],
            'transaction_id' => ['nullable', 'string', 'max:255', 'unique:payments,transaction_id'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
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
            'invoice_id.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.invoice_id')]),
            'invoice_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.invoice_id')]),
            'method.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.payment_method')]),
            'method.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.payment_method')]),
            'transaction_id.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.transaction_id')]),
            'amount.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.amount')]),
            'amount.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.amount')]),
            'paid_at.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.payment_date')]),
            'status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.status')]),
        ];
    }
}

