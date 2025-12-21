<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'email' => ['required_without:phone', 'nullable', 'email'],
            'phone' => ['required_without:email', 'nullable', 'string', new \App\Rules\SaudiPhoneNumber()],
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'session_id' => ['required', 'string'],
            'purpose' => ['nullable', 'string', 'in:login,forgot_password'], // Allow login or forgot_password
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone if provided
        if ($this->has('phone') && $this->phone) {
            $this->merge([
                'phone' => \App\Rules\SaudiPhoneNumber::normalize($this->phone),
            ]);
        }
    }
}

