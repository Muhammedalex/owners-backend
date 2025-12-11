<?php

namespace App\Http\Requests\V1\Contract;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\Contract\ContractTerm::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contractId = $this->route('contract')->id ?? null;
        $ownershipId = request()->input('current_ownership_id');
        if (!$ownershipId || !$contractId) {
            return [];
        }

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contract_terms', 'key')->where('contract_id', $contractId),
            ],
            'value' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:50', Rule::in(['text', 'number', 'boolean', 'date', 'json'])],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'key' => __('messages.attributes.key'),
            'value' => __('messages.attributes.value'),
            'type' => __('messages.attributes.type'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'key.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.key')]),
            'key.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.key')]),
            'type.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.type')]),
        ];
    }
}

