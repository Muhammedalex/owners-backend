<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnershipBoardMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $boardMember = $this->route('boardMember');
        return $this->user()->can('update', $boardMember);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'required', 'string', 'max:50'],
            'active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
