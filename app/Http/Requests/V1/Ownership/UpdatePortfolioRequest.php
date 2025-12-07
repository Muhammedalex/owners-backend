<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortfolioRequest extends FormRequest
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
        $portfolioId = $this->route('portfolio');
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
                Rule::unique('portfolios', 'code')
                    ->where(function ($query) use ($ownershipId) {
                        return $query->where('ownership_id', $ownershipId);
                    })
                    ->ignore($portfolioId),
            ],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('portfolios', 'id')
                    ->where(function ($query) use ($ownershipId) {
                        return $query->where('ownership_id', $ownershipId);
                    })
                    ->ignore($portfolioId), // Prevent self-reference
            ],
            'active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
