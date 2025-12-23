<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }

        // Super Admin can always import
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must have either auth.users.view or ownerships.users.assign permission
        return $user->can('auth.users.view') || $user->can('ownerships.users.assign');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentUser = $this->user();
        $targetOwnershipId = $this->input('current_ownership_id');

        return [
            'source_ownership_id' => [
                'required',
                'integer',
                Rule::exists('ownerships', 'id'),
                function ($attribute, $value, $fail) use ($currentUser, $targetOwnershipId) {
                    // Validate user has access to source ownership
                    if (!$currentUser->isSuperAdmin() && !$currentUser->hasOwnership($value)) {
                        $fail('You do not have access to the source ownership.');
                    }

                    // Source and target cannot be the same
                    if ($value == $targetOwnershipId) {
                        $fail('Source and target ownership cannot be the same.');
                    }
                },
            ],
            'user_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'user_ids.*' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'create_tenant_if_needed' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'source_ownership_id' => __('messages.attributes.ownership_id'),
            'user_ids' => __('messages.attributes.users'),
            'user_ids.*' => __('messages.attributes.user_id'),
            'create_tenant_if_needed' => 'create tenant if needed',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'source_ownership_id.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.ownership_id')]),
            'source_ownership_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.ownership_id')]),
            'user_ids.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.users')]),
            'user_ids.array' => __('messages.validation.array', ['attribute' => __('messages.attributes.users')]),
            'user_ids.min' => __('messages.validation.min.array', ['attribute' => __('messages.attributes.users'), 'min' => 1]),
            'user_ids.*.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.user_id')]),
            'user_ids.*.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.user_id')]),
        ];
    }
}

