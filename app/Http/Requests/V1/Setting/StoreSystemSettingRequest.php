<?php

namespace App\Http\Requests\V1\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSystemSettingRequest extends FormRequest
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
        $ownershipId = $this->input('ownership_id');

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('system_settings', 'key')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'value' => ['nullable'],
            'value_type' => [
                'required',
                'string',
                Rule::in(['string', 'integer', 'decimal', 'boolean', 'json', 'array']),
            ],
            'group' => [
                'required',
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
            'ownership_id' => ['nullable', 'integer', 'exists:ownerships,id'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If ownership_id is not provided, it means system-wide setting
        // Only Super Admin can create system-wide settings
        if (!$this->has('ownership_id')) {
            $this->merge([
                'ownership_id' => null,
            ]);
        }

        // Validate value based on value_type
        if ($this->has('value') && $this->has('value_type')) {
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
            'key' => __('messages.attributes.key'),
            'value' => __('messages.attributes.value'),
            'value_type' => __('messages.attributes.value_type'),
            'group' => __('messages.attributes.group'),
            'description' => __('messages.attributes.description'),
            'ownership_id' => __('messages.attributes.ownership_id'),
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
            'value_type.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.value_type')]),
            'value_type.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.value_type')]),
            'group.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.group')]),
            'group.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.group')]),
            'ownership_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.ownership_id')]),
        ];
    }
}

