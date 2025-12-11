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
        $currentUser = $this->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        $rules = [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'default' => ['nullable', 'boolean'],
        ];

        // Super Admin must provide ownership_uuid or ownership_id
        if ($isSuperAdmin) {
            $rules['ownership_uuid'] = [
                'required_without:ownership_id',
                'nullable',
                'string',
                'exists:ownerships,uuid',
            ];
            $rules['ownership_id'] = [
                'required_without:ownership_uuid',
                'nullable',
                'integer',
                'exists:ownerships,id',
            ];
        }
        // Non-Super Admin: ownership_id comes from middleware (current_ownership_id)
        // No need to validate it here as it's set by middleware

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $currentUser = $this->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        // If Super Admin provided ownership_uuid, convert it to ownership_id
        if ($isSuperAdmin && $this->has('ownership_uuid') && !$this->has('ownership_id')) {
            $ownership = \App\Models\V1\Ownership\Ownership::where('uuid', $this->input('ownership_uuid'))->first();
            if ($ownership) {
                $this->merge([
                    'ownership_id' => $ownership->id,
                ]);
            }
        }
    }
}
