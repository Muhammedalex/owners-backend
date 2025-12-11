<?php

namespace App\Http\Requests\V1\Auth;

use App\Rules\SaudiPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', new SaudiPhoneNumber(), 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'first' => ['nullable', 'string', 'max:100'],
            'last' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => __('messages.attributes.email'),
            'phone' => __('messages.attributes.phone'),
            'password' => __('messages.attributes.password'),
            'first' => __('messages.attributes.first'),
            'last' => __('messages.attributes.last'),
            'company' => __('messages.attributes.company'),
            'type' => __('messages.attributes.type'),
            'device_name' => __('messages.attributes.device_name'),
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
            'phone.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.phone')]),
            'password.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.password')]),
            'password.confirmed' => __('messages.validation.confirmed', ['attribute' => __('messages.attributes.password')]),
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
}

