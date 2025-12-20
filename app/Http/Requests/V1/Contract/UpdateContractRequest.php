<?php

namespace App\Http\Requests\V1\Contract;

use App\Services\V1\Contract\ContractSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contract'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contractId = $this->route('contract')->id ?? null;
        $ownershipId = request()->input('current_ownership_id');
        if (!$ownershipId) {
            return [];
        }

        return [
            // Single source of truth for units on update
            'units' => ['sometimes', 'array', 'min:1'],
            'units.*.unit_id' => [
                'required_with:units',
                'integer',
                Rule::exists('units', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            'units.*.rent_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'units.*.notes' => ['nullable', 'string'],
            'tenant_id' => [
                'sometimes',
                'integer',
                Rule::exists('tenants', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            // ownership_id cannot be changed via update (taken from middleware scope)
            'number' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('contracts', 'number')
                    ->where(function ($query) use ($ownershipId) {
                        return $query->where('ownership_id', $ownershipId);
                    })
                    ->ignore($contractId),
            ],
            'version' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'ejar_code' => ['nullable', 'string', 'max:100'], // Optional ejar registration code
            'start' => ['sometimes', 'date'],
            'end' => ['sometimes', 'date', 'after:start'],
            'base_rent' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'rent_fees' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'vat_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'total_rent' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'previous_balance' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_frequency' => ['nullable', 'string', 'max:50', Rule::in(['monthly', 'quarterly', 'yearly', 'weekly'])],
            'deposit' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'deposit_status' => ['nullable', 'string', 'max:50', Rule::in(['pending', 'paid', 'refunded', 'forfeited'])],
            'document' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'string'],
            // Status can only be draft or pending (not active - use approve endpoint for that)
            'status' => ['nullable', 'string', 'max:50', Rule::in(['draft', 'pending'])],
            'ejar_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $ownershipId = request()->input('current_ownership_id');
        if (!$ownershipId) {
            return;
        }

        $settingService = app(ContractSettingService::class);
        $contract = $this->route('contract');

        $validator->after(function ($validator) use ($settingService, $ownershipId, $contract) {
            $data = $this->all();

            // Validate contract duration if dates are being updated
            if (isset($data['start']) || isset($data['end'])) {
                $startDate = isset($data['start']) 
                    ? \Carbon\Carbon::parse($data['start']) 
                    : \Carbon\Carbon::parse($contract->start);
                $endDate = isset($data['end']) 
                    ? \Carbon\Carbon::parse($data['end']) 
                    : \Carbon\Carbon::parse($contract->end);
                
                $durationMonths = $startDate->diffInMonths($endDate);

                $minDuration = $settingService->getMinContractDurationMonths($ownershipId);
                $maxDuration = $settingService->getMaxContractDurationMonths($ownershipId);

                if ($durationMonths < $minDuration) {
                    $validator->errors()->add('end', __('messages.validation.custom.contract_duration_too_short', [
                        'min' => $minDuration
                    ]));
                }

                if ($durationMonths > $maxDuration) {
                    $validator->errors()->add('end', __('messages.validation.custom.contract_duration_too_long', [
                        'max' => $maxDuration
                    ]));
                }
            }

            // Validate max units per contract
            if (isset($data['units']) && is_array($data['units'])) {
                $maxUnits = $settingService->getMaxUnitsPerContract($ownershipId);
                if (count($data['units']) > $maxUnits) {
                    $validator->errors()->add('units', __('messages.validation.max', [
                        'attribute' => __('messages.attributes.units'),
                        'max' => $maxUnits
                    ]));
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'units' => __('messages.attributes.unit_id'),
            'units.*.unit_id' => __('messages.attributes.unit_id'),
            'tenant_id' => __('messages.attributes.tenant_id'),
            'number' => __('messages.attributes.number'),
            'version' => __('messages.attributes.version'),
            'parent_id' => __('messages.attributes.parent_id'),
            'ejar_code' => __('messages.attributes.ejar_code'),
            'start' => __('messages.attributes.start_date'),
            'end' => __('messages.attributes.end_date'),
            'base_rent' => __('messages.attributes.base_rent'),
            'rent_fees' => __('messages.attributes.rent_fees'),
            'vat_amount' => __('messages.attributes.vat_amount'),
            'total_rent' => __('messages.attributes.total_rent'),
            'previous_balance' => __('messages.attributes.previous_balance'),
            'payment_frequency' => __('messages.attributes.payment_frequency'),
            'deposit' => __('messages.attributes.deposit'),
            'deposit_status' => __('messages.attributes.deposit_status'),
            'document' => __('messages.attributes.document'),
            'signature' => __('messages.attributes.signature'),
            'ejar_pdf' => __('messages.attributes.document'),
            'status' => __('messages.attributes.status'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'units.*.unit_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.unit_id')]),
            'tenant_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.tenant_id')]),
            'number.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.number')]),
            'start.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.start_date')]),
            'end.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.end_date')]),
            'payment_frequency.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.payment_frequency')]),
            'deposit_status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.deposit_status')]),
            'status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.status')]),
            'ejar_pdf.mimes' => __('messages.validation.invalid_file_type', ['attribute' => __('messages.attributes.document')]),
        ];
    }
}

