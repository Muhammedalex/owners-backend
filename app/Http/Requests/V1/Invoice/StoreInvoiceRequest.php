<?php

namespace App\Http\Requests\V1\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\Invoice\Invoice::class);
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
            'contract_id' => [
                'required',
                'integer',
                Rule::exists('contracts', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            // ownership_id is taken from middleware (current_ownership_id), not from request
            'number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('invoices', 'number')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'due' => ['required', 'date', 'after_or_equal:period_start'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['nullable', 'string', 'max:50', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contract_id' => __('messages.attributes.contract_id'),
            'number' => __('messages.attributes.number'),
            'period_start' => __('messages.attributes.period_start'),
            'period_end' => __('messages.attributes.period_end'),
            'due' => __('messages.attributes.due'),
            'amount' => __('messages.attributes.amount'),
            'tax' => __('messages.attributes.tax'),
            'tax_rate' => __('messages.attributes.tax_rate'),
            'total' => __('messages.attributes.total'),
            'status' => __('messages.attributes.status'),
            'notes' => __('messages.attributes.notes'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contract_id.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.contract_id')]),
            'contract_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.contract_id')]),
            'number.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.number')]),
            'number.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.number')]),
            'period_start.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.period_start')]),
            'period_start.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.period_start')]),
            'period_end.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.period_end')]),
            'period_end.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.period_end')]),
            'due.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.due')]),
            'due.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.due')]),
            'amount.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.amount')]),
            'amount.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.amount')]),
            'tax.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.tax')]),
            'tax_rate.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.tax_rate')]),
            'total.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.total')]),
            'status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.status')]),
        ];
    }
}

