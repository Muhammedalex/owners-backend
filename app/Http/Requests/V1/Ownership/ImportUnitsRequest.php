<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;

class ImportUnitsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\Ownership\Unit::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10MB max
            ],
            'building_id' => [
                'nullable',
                'integer',
                'exists:buildings,id',
            ],
            'skip_errors' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'file' => __('messages.attributes.file'),
            'building_id' => __('messages.attributes.building_id'),
            'skip_errors' => __('messages.attributes.skip_errors'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.file')]),
            'file.mimes' => __('messages.validation.mimes', [
                'attribute' => __('messages.attributes.file'),
                'values' => 'xlsx, xls',
            ]),
            'file.max' => __('messages.validation.max', [
                'attribute' => __('messages.attributes.file'),
                'max' => '10MB',
            ]),
            'building_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.building_id')]),
        ];
    }
}

