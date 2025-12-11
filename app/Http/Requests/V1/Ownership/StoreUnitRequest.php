<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $ownershipId = request()->input('current_ownership_id');
        if (!$ownershipId) {
            return [];
        }

        return [
            'building_id' => [
                'required',
                'integer',
                Rule::exists('buildings', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'floor_id' => [
                'nullable',
                'integer',
                Rule::exists('building_floors', 'id')->where(function ($query) {
                    return $query->where('building_id', $this->input('building_id'));
                }),
            ],
            'number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'number')->where(function ($query) {
                    return $query->where('building_id', $this->input('building_id'));
                }),
            ],
            'type' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'area' => ['required', 'numeric', 'min:0'],
            'price_monthly' => ['nullable', 'numeric', 'min:0'],
            'price_quarterly' => ['nullable', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50', Rule::in(['available', 'rented', 'maintenance', 'reserved', 'sold'])],
            'active' => ['nullable', 'boolean'],
            'specifications' => ['nullable', 'array'],
            'specifications.*.key' => ['required_with:specifications', 'string', 'max:255'],
            'specifications.*.value' => ['nullable', 'string'],
            'specifications.*.type' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ownership ID will be set from middleware (current_ownership_id)
        // It will be added in the controller before calling service
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'building_id' => __('messages.attributes.building_id'),
            'floor_id' => __('messages.attributes.floor_id'),
            'number' => __('messages.attributes.number'),
            'type' => __('messages.attributes.type'),
            'name' => __('messages.attributes.name'),
            'description' => __('messages.attributes.description'),
            'area' => __('messages.attributes.area'),
            'price_monthly' => __('messages.attributes.price_monthly'),
            'price_quarterly' => __('messages.attributes.price_quarterly'),
            'price_yearly' => __('messages.attributes.price_yearly'),
            'status' => __('messages.attributes.status'),
            'active' => __('messages.attributes.active'),
            'specifications' => __('messages.attributes.specifications'),
            'specifications.*.key' => __('messages.attributes.key'),
            'specifications.*.value' => __('messages.attributes.value'),
            'specifications.*.type' => __('messages.attributes.type'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'building_id.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.building_id')]),
            'building_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.building_id')]),
            'floor_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.floor_id')]),
            'number.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.number')]),
            'number.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.number')]),
            'type.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.type')]),
            'area.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.area')]),
            'area.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.area')]),
            'area.min' => __('messages.validation.min', ['attribute' => __('messages.attributes.area'), 'min' => 0]),
            'price_monthly.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.price_monthly')]),
            'price_monthly.min' => __('messages.validation.min', ['attribute' => __('messages.attributes.price_monthly'), 'min' => 0]),
            'price_quarterly.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.price_quarterly')]),
            'price_quarterly.min' => __('messages.validation.min', ['attribute' => __('messages.attributes.price_quarterly'), 'min' => 0]),
            'price_yearly.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.price_yearly')]),
            'price_yearly.min' => __('messages.validation.min', ['attribute' => __('messages.attributes.price_yearly'), 'min' => 0]),
            'status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.status')]),
            'specifications.array' => __('messages.validation.array', ['attribute' => __('messages.attributes.specifications')]),
            'specifications.*.key.required_with' => __('messages.validation.required', ['attribute' => __('messages.attributes.key')]),
        ];
    }
}
