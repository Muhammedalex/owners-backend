<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingFloorRequest extends FormRequest
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
        $floorId = $this->route('buildingFloor');
        $ownershipId = request()->input('current_ownership_id');
        
        if (!$ownershipId) {
            return [];
        }

        $buildingId = $this->input('building_id', $this->route('buildingFloor')->building_id ?? null);

        return [
            'building_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('buildings', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'number' => [
                'sometimes',
                'required',
                'integer',
                Rule::unique('building_floors', 'number')
                    ->where(function ($query) use ($buildingId) {
                        return $query->where('building_id', $buildingId);
                    })
                    ->ignore($floorId),
            ],
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'units' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
