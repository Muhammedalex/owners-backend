<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
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
        $unitId = $this->route('unit');
        $ownershipId = request()->input('current_ownership_id');
        
        if (!$ownershipId) {
            return [];
        }

        $buildingId = $this->input('building_id', $this->route('unit')->building_id ?? null);

        return [
            'building_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('buildings', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'floor_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('building_floors', 'id')->where(function ($query) use ($buildingId) {
                    return $query->where('building_id', $buildingId);
                }),
            ],
            'number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'number')
                    ->where(function ($query) use ($buildingId) {
                        return $query->where('building_id', $buildingId);
                    })
                    ->ignore($unitId),
            ],
            'type' => ['sometimes', 'required', 'string', 'max:50'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'area' => ['sometimes', 'required', 'numeric', 'min:0'],
            'price_monthly' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_quarterly' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_yearly' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50', Rule::in(['available', 'rented', 'maintenance', 'reserved', 'sold'])],
            'active' => ['sometimes', 'nullable', 'boolean'],
            'specifications' => ['sometimes', 'nullable', 'array'],
            'specifications.*.key' => ['required_with:specifications', 'string', 'max:255'],
            'specifications.*.value' => ['nullable', 'string'],
            'specifications.*.type' => ['nullable', 'string', 'max:50'],
        ];
    }
}
