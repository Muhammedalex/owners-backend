<?php

namespace App\Http\Requests\V1\Auth;

use App\Rules\SaudiPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', new SaudiPhoneNumber(), 'max:20', 'unique:users,phone'],
            'first' => ['nullable', 'string', 'max:100'],
            'last' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'type' => [
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
            'active' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'max:10'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
            'is_default' => ['nullable', 'boolean'], // For non-Super Admin: set as default ownership
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
            'is_default' => __('messages.attributes.is_default'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.email')]),
            'email.email' => __('messages.validation.email', ['attribute' => __('messages.attributes.email')]),
            'email.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.email')]),
            'password.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.password')]),
            'password.confirmed' => __('messages.validation.confirmed', ['attribute' => __('messages.attributes.password')]),
            'phone.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.phone')]),
            'roles.*.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.roles')]),
        ];
    }
}

