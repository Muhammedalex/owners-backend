<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortfolioRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('portfolios', 'code')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('portfolios', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'active' => ['nullable', 'boolean'],
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
