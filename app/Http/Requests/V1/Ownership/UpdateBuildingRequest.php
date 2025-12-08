<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends FormRequest
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
        $buildingId = $this->route('building');
        $ownershipId = request()->input('current_ownership_id');
        
        if (!$ownershipId) {
            return [];
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('buildings', 'code')
                    ->where(function ($query) use ($ownershipId) {
                        return $query->where('ownership_id', $ownershipId);
                    })
                    ->ignore($buildingId),
            ],
            'type' => ['sometimes', 'required', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'portfolio_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('portfolios', 'id')
                    ->where(function ($query) use ($ownershipId) {
                        return $query->where('ownership_id', $ownershipId);
                    }),
            ],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'floors' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:1800', 'max:' . (date('Y') + 10)],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('buildings', 'id')
                    ->where(function ($query) use ($ownershipId, $buildingId) {
                        return $query->where('ownership_id', $ownershipId)
                                     ->where('id', '!=', $buildingId); // Prevent self-reference
                    }),
            ],
            'active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
