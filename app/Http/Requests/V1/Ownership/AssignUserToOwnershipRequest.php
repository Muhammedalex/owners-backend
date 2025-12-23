<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignUserToOwnershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('ownerships.users.assign');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'default' => ['nullable', 'boolean'],
            'ownership_uuid' => [
                'nullable',
                'string',
                'exists:ownerships,uuid',
            ],
            'ownership_id' => [
                'nullable',
                'integer',
                'exists:ownerships,id',
            ],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If ownership_uuid is provided, convert it to ownership_id
        if ($this->has('ownership_uuid') && !$this->has('ownership_id')) {
            $ownership = \App\Models\V1\Ownership\Ownership::where('uuid', $this->input('ownership_uuid'))->first();
            if ($ownership) {
                $this->merge([
                    'ownership_id' => $ownership->id,
                ]);
            }
        }
    }
}
