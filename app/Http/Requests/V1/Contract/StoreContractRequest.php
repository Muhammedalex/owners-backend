<?php

namespace App\Http\Requests\V1\Contract;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\Contract\Contract::class);
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
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'tenant_id' => [
                'required',
                'integer',
                Rule::exists('tenants', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            // ownership_id is taken from middleware (current_ownership_id), not from request
            'number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('contracts', 'number')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'version' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'ejar_code' => ['nullable', 'string', 'max:100'], // Optional ejar registration code
            'start' => ['required', 'date', 'after_or_equal:today'],
            'end' => ['required', 'date', 'after:start'],
            'rent' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_frequency' => ['nullable', 'string', 'max:50', Rule::in(['monthly', 'quarterly', 'yearly', 'weekly'])],
            'deposit' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'deposit_status' => ['nullable', 'string', 'max:50', Rule::in(['pending', 'paid', 'refunded', 'forfeited'])],
            'document' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50', Rule::in(['draft', 'pending', 'active', 'expired', 'terminated', 'cancelled'])],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'unit_id' => __('messages.attributes.unit_id'),
            'tenant_id' => __('messages.attributes.tenant_id'),
            'number' => __('messages.attributes.number'),
            'version' => __('messages.attributes.version'),
            'parent_id' => __('messages.attributes.parent_id'),
            'ejar_code' => __('messages.attributes.ejar_code'),
            'start' => __('messages.attributes.start_date'),
            'end' => __('messages.attributes.end_date'),
            'rent' => __('messages.attributes.rent'),
            'payment_frequency' => __('messages.attributes.payment_frequency'),
            'deposit' => __('messages.attributes.deposit'),
            'deposit_status' => __('messages.attributes.deposit_status'),
            'document' => __('messages.attributes.document'),
            'signature' => __('messages.attributes.signature'),
            'status' => __('messages.attributes.status'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'unit_id.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.unit_id')]),
            'unit_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.unit_id')]),
            'tenant_id.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.tenant_id')]),
            'tenant_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.tenant_id')]),
            'number.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.number')]),
            'number.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.number')]),
            'start.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.start_date')]),
            'start.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.start_date')]),
            'end.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.end_date')]),
            'end.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.end_date')]),
            'rent.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.rent')]),
            'rent.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.rent')]),
            'payment_frequency.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.payment_frequency')]),
            'deposit_status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.deposit_status')]),
            'status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.status')]),
        ];
    }
}

