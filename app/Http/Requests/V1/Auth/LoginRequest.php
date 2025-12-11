<?php

namespace App\Http\Requests\V1\Auth;

use App\Rules\SaudiPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => [
                'required_without:phone',
                'nullable',
                'string',
                'email',
            ],
            'phone' => [
                'required_without:email',
                'nullable',
                new SaudiPhoneNumber(),
            ],
            'password' => ['required', 'string'],
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
            'device_name' => __('messages.attributes.device_name'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required_without' => __('messages.validation.required', ['attribute' => __('messages.attributes.email')]),
            'phone.required_without' => __('messages.validation.required', ['attribute' => __('messages.attributes.phone')]),
            'password.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.password')]),
            'email.email' => __('messages.validation.email', ['attribute' => __('messages.attributes.email')]),
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

