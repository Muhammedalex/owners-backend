<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuildingFloorRequest extends FormRequest
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
            'number' => [
                'required',
                'integer',
                Rule::unique('building_floors', 'number')->where(function ($query) {
                    return $query->where('building_id', $this->input('building_id'));
                }),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'units' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ownership ID will be set from middleware (current_ownership_id)
    }
}
