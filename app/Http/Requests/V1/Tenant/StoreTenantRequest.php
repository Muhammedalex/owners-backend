<?php

namespace App\Http\Requests\V1\Tenant;

use App\Rules\SaudiPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\Tenant\Tenant::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ownershipId = request()->input('current_ownership_id');
        if (!$ownershipId) {
            return [];
        }

        return [
            'user_id' => ['required', 'integer', 'exists:users,id', 'unique:tenants,user_id'],
            // ownership_id is taken from middleware (current_ownership_id), not from request
            'national_id' => ['nullable', 'string', 'max:50'],
            'id_type' => ['nullable', 'string', 'max:50', Rule::in(['national_id', 'iqama', 'passport', 'commercial_registration'])],
            'id_document' => ['nullable', 'string', 'max:255'],
            'id_expiry' => ['nullable', 'date'],
            'commercial_registration_number' => ['nullable', 'string', 'max:100'],
            'commercial_registration_expiry' => ['nullable', 'date'],
            'commercial_owner_name' => ['nullable', 'string', 'max:255'],
            'municipality_license_number' => ['nullable', 'string', 'max:100'],
            'activity_name' => ['nullable', 'string', 'max:255'],
            'activity_type' => ['nullable', 'string', 'max:100'],
            'emergency_name' => ['nullable', 'string', 'max:100'],
            'emergency_phone' => ['nullable', new SaudiPhoneNumber(), 'max:20'],
            'emergency_relation' => ['nullable', 'string', 'max:50'],
            'employment' => ['nullable', 'string', 'max:50', Rule::in(['employed', 'self_employed', 'unemployed', 'retired', 'student'])],
            'employer' => ['nullable', 'string', 'max:255'],
            'income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'rating' => ['nullable', 'string', 'max:50', Rule::in(['excellent', 'good', 'fair', 'poor'])],
            'notes' => ['nullable', 'string'],
            // Optional files uploaded with store endpoint
            'id_document_image' => ['nullable', 'file', 'image'],
            'commercial_registration_image' => ['nullable', 'file', 'image'],
            'municipality_license_image' => ['nullable', 'file', 'image'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
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
            'user_id' => __('messages.attributes.user_id'),
            'national_id' => __('messages.attributes.national_id'),
            'id_type' => __('messages.attributes.id_type'),
            'id_document' => __('messages.attributes.id_document'),
            'id_expiry' => __('messages.attributes.id_expiry'),
            'commercial_registration_number' => __('messages.attributes.commercial_registration_number'),
            'commercial_registration_expiry' => __('messages.attributes.commercial_registration_expiry'),
            'commercial_owner_name' => __('messages.attributes.commercial_owner_name'),
            'municipality_license_number' => __('messages.attributes.municipality_license_number'),
            'activity_name' => __('messages.attributes.activity_name'),
            'activity_type' => __('messages.attributes.activity_type'),
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

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.user_id')]),
            'user_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.user_id')]),
            'user_id.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.user_id')]),
            'id_type.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.id_type')]),
            'employment.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.employment')]),
            'rating.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.rating')]),
            'income.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.income')]),
            'id_expiry.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.id_expiry')]),
            'commercial_registration_expiry.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.commercial_registration_expiry')]),
        ];
    }
}

