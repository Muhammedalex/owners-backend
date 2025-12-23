<?php

namespace App\Http\Requests\V1\Auth;

use App\Rules\SaudiPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user');

        return [
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'phone' => [
                'sometimes',
                'nullable',
                new SaudiPhoneNumber(),
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'first' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last' => ['sometimes', 'nullable', 'string', 'max:100'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::in([
                    'owner',
                    'tenant',
                    'accountant',
                    'moderator',
                    'board_member',
                    'property_manager',
                    'maintenance_manager',
                    'facility_manager',
                    'collector',
                ]),
            ],
            'active' => ['sometimes', 'nullable', 'boolean'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:10'],
            'roles' => ['sometimes', 'nullable', 'array'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && !empty($this->phone)) {
            $this->merge([
                'phone' => SaudiPhoneNumber::normalize($this->phone),
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => __('messages.attributes.email'),
            'password' => __('messages.attributes.password'),
            'phone' => __('messages.attributes.phone'),
            'first' => __('messages.attributes.first'),
            'last' => __('messages.attributes.last'),
            'company' => __('messages.attributes.company'),
            'type' => __('messages.attributes.type'),
            'active' => __('messages.attributes.active'),
            'timezone' => __('messages.attributes.timezone'),
            'locale' => __('messages.attributes.locale'),
            'roles' => __('messages.attributes.roles'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.email' => __('messages.validation.email', ['attribute' => __('messages.attributes.email')]),
            'email.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.email')]),
            'password.confirmed' => __('messages.validation.confirmed', ['attribute' => __('messages.attributes.password')]),
            'phone.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.phone')]),
            'roles.*.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.roles')]),
        ];
    }
}

