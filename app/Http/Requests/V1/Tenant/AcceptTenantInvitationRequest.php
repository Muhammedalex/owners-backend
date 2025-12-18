<?php

namespace App\Http\Requests\V1\Tenant;

use App\Rules\SaudiPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcceptTenantInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public endpoint - no authorization required
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', new SaudiPhoneNumber(), 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'id_type' => ['nullable', 'string', 'max:50', Rule::in(['national_id', 'iqama', 'passport', 'commercial_registration'])],
            'id_expiry' => ['nullable', 'date'],
            'id_document_image' => ['nullable', 'file', 'image'],
            'commercial_registration_number' => ['nullable', 'string', 'max:100'],
            'commercial_registration_expiry' => ['nullable', 'date'],
            'commercial_owner_name' => ['nullable', 'string', 'max:255'],
            'commercial_registration_image' => ['nullable', 'file', 'image'],
            'municipality_license_number' => ['nullable', 'string', 'max:100'],
            'municipality_license_image' => ['nullable', 'file', 'image'],
            'emergency_name' => ['nullable', 'string', 'max:100'],
            'emergency_phone' => ['nullable', new SaudiPhoneNumber(), 'max:20'],
            'emergency_relation' => ['nullable', 'string', 'max:50'],
            'employment' => ['nullable', 'string', 'max:50', Rule::in(['employed', 'self_employed', 'unemployed', 'retired', 'student'])],
            'employer' => ['nullable', 'string', 'max:255'],
            'income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            // 'rating' => ['nullable', 'string', 'max:50', Rule::in(['excellent', 'good', 'fair', 'poor'])],
            'notes' => ['nullable', 'string'],
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

        if ($this->has('emergency_phone') && !empty($this->emergency_phone)) {
            $this->merge([
                'emergency_phone' => SaudiPhoneNumber::normalize($this->emergency_phone),
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => __('messages.attributes.first_name'),
            'last_name' => __('messages.attributes.last_name'),
            'email' => __('messages.attributes.email'),
            'phone' => __('messages.attributes.phone'),
            'password' => __('messages.attributes.password'),
            'password_confirmation' => __('messages.attributes.password_confirmation'),
            'national_id' => __('messages.attributes.national_id'),
            'id_type' => __('messages.attributes.id_type'),
            'id_expiry' => __('messages.attributes.id_expiry'),
            'id_document_image' => __('messages.attributes.id_document'),
            'commercial_registration_number' => __('messages.attributes.commercial_registration_number'),
            'commercial_registration_expiry' => __('messages.attributes.commercial_registration_expiry'),
            'commercial_owner_name' => __('messages.attributes.commercial_owner_name'),
            'commercial_registration_image' => __('messages.attributes.commercial_registration_number'),
            'municipality_license_number' => __('messages.attributes.municipality_license_number'),
            'municipality_license_image' => __('messages.attributes.municipality_license_number'),
            'emergency_name' => __('messages.attributes.emergency_name'),
            'emergency_phone' => __('messages.attributes.emergency_phone'),
            'emergency_relation' => __('messages.attributes.emergency_relation'),
            'employment' => __('messages.attributes.employment'),
            'employer' => __('messages.attributes.employer'),
            'income' => __('messages.attributes.income'),
            'rating' => __('messages.attributes.rating'),
            'notes' => __('messages.attributes.notes'),
        ];
    }
}

