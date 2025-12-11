<?php

namespace App\Http\Requests\V1\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\Invoice\InvoiceItem::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50', Rule::in(['rent', 'utilities', 'maintenance', 'penalty', 'other'])],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'total' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type' => __('messages.attributes.type'),
            'description' => __('messages.attributes.description'),
            'quantity' => __('messages.attributes.quantity'),
            'unit_price' => __('messages.attributes.unit_price'),
            'total' => __('messages.attributes.total'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.type')]),
            'type.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.type')]),
            'description.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.description')]),
            'quantity.integer' => __('messages.validation.integer', ['attribute' => __('messages.attributes.quantity')]),
            'unit_price.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.unit_price')]),
            'unit_price.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.unit_price')]),
            'total.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.total')]),
        ];
    }
}

