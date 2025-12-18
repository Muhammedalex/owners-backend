<?php

namespace App\Http\Requests\V1\Tenant;

use App\Rules\SaudiPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\Tenant\TenantInvitation::class);
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
            // Either email or phone must be provided
            'email' => [
                'required_without:phone',
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'required_without:email',
                'nullable',
                new SaudiPhoneNumber(),
                'max:20',
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
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
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => __('messages.attributes.email'),
            'phone' => __('messages.attributes.phone'),
            'name' => __('messages.attributes.name'),
            'expires_in_days' => __('messages.attributes.expires_in_days'),
            'notes' => __('messages.attributes.notes'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required_without' => __('messages.validation.required_without', [
                'attribute' => __('messages.attributes.email'),
                'other' => __('messages.attributes.phone'),
            ]),
            'phone.required_without' => __('messages.validation.required_without', [
                'attribute' => __('messages.attributes.phone'),
                'other' => __('messages.attributes.email'),
            ]),
            'email.email' => __('messages.validation.email', ['attribute' => __('messages.attributes.email')]),
            'expires_in_days.integer' => __('messages.validation.integer', ['attribute' => __('messages.attributes.expires_in_days')]),
            'expires_in_days.min' => __('messages.validation.min.numeric', [
                'attribute' => __('messages.attributes.expires_in_days'),
                'min' => 1,
            ]),
            'expires_in_days.max' => __('messages.validation.max.numeric', [
                'attribute' => __('messages.attributes.expires_in_days'),
                'max' => 30,
            ]),
        ];
    }
}

