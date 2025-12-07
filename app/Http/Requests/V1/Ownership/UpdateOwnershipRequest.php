<?php

namespace App\Http\Requests\V1\Ownership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ownership = $this->route('ownership');
        return $this->user()->can('update', $ownership);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ownership = $this->route('ownership');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'legal' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'max:50'],
            'ownership_type' => ['sometimes', 'required', 'string', 'max:50'],
            'registration' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('ownerships', 'registration')->ignore($ownership->id),
            ],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
