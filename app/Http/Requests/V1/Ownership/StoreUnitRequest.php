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
}
