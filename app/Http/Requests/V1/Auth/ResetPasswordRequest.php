<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            // Token-based reset (legacy email method)
            'token' => ['required_without:otp', 'nullable', 'string'],
            'email' => ['required_without_all:phone,otp', 'nullable', 'email', 'exists:users,email'],
            
            // OTP-based reset (new method)
            'otp' => ['required_without:token', 'nullable', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'phone' => ['required_without_all:email,token', 'nullable', 'string', new \App\Rules\SaudiPhoneNumber(), 'exists:users,phone'],
            'session_id' => ['required_with:otp', 'nullable', 'string'],
            
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
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

