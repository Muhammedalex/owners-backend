<?php

namespace App\Http\Requests\V1\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be handled in controller based on group permissions
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Setting will be loaded in controller

        return [
            'value' => ['nullable'],
            'value_type' => [
                'sometimes',
                'string',
                Rule::in(['string', 'integer', 'decimal', 'boolean', 'json', 'array']),
            ],
            'group' => [
                'sometimes',
                'string',
                'max:50',
                Rule::in([
                    'financial',
                    'contract',
                    'invoice',
                    'tenant',
                    'notification',
                    'maintenance',
                    'facility',
                    'document',
                    'media',
                    'reporting',
                    'localization',
                    'security',
                    'system',
                ]),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Validate value based on value_type
        if ($this->has('value')) {
            $value = $this->input('value');
            $valueType = $this->input('value_type');

            // Convert value to appropriate format
            if ($valueType === 'json' || $valueType === 'array') {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->merge(['value' => $decoded]);
                    }
                }
            } elseif ($valueType === 'boolean') {
                $this->merge(['value' => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);
            } elseif ($valueType === 'integer') {
                $this->merge(['value' => (int) $value]);
            } elseif ($valueType === 'decimal') {
                $this->merge(['value' => (float) $value]);
            }
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'value' => __('messages.attributes.value'),
            'value_type' => __('messages.attributes.value_type'),
            'group' => __('messages.attributes.group'),
            'description' => __('messages.attributes.description'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'value_type.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.value_type')]),
            'group.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.group')]),
        ];
    }
}

