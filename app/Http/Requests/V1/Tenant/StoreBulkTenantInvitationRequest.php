<?php

namespace App\Http\Requests\V1\Tenant;

use App\Rules\SaudiPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreBulkTenantInvitationRequest extends FormRequest
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
        return [
            'invitations' => ['required', 'array', 'min:1', 'max:100'],
            'invitations.*.email' => [
                'required_without:invitations.*.phone',
                'nullable',
                'email',
                'max:255',
            ],
            'invitations.*.phone' => [
                'required_without:invitations.*.email',
                'nullable',
                new SaudiPhoneNumber(),
                'max:20',
            ],
            'invitations.*.name' => ['nullable', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('invitations')) {
            $invitations = [];
            foreach ($this->invitations as $invitation) {
                if (isset($invitation['phone']) && !empty($invitation['phone'])) {
                    $invitation['phone'] = SaudiPhoneNumber::normalize($invitation['phone']);
                }
                $invitations[] = $invitation;
            }
            $this->merge(['invitations' => $invitations]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'invitations' => __('messages.attributes.invitations'),
            'invitations.*.email' => __('messages.attributes.email'),
            'invitations.*.phone' => __('messages.attributes.phone'),
            'invitations.*.name' => __('messages.attributes.name'),
            'expires_in_days' => __('messages.attributes.expires_in_days'),
            'notes' => __('messages.attributes.notes'),
        ];
    }
}

