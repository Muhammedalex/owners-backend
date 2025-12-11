<?php

namespace App\Http\Requests\V1\Contract;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('term'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $term = $this->route('term');
        $contractId = $term->contract_id ?? null;
        $termId = $term->id ?? null;

        return [
            'key' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('contract_terms', 'key')
                    ->where('contract_id', $contractId)
                    ->ignore($termId),
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
            'key.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.key')]),
            'type.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.type')]),
        ];
    }
}

