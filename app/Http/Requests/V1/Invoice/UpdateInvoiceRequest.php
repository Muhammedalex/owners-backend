<?php

namespace App\Http\Requests\V1\Invoice;

use App\Models\V1\Contract\Contract;
use App\Services\V1\Invoice\ContractInvoiceService;
use App\Services\V1\Invoice\InvoiceEditRulesService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('invoice'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $invoice = $this->route('invoice');
        $invoiceId = $invoice->id ?? null;
        $ownershipId = request()->input('current_ownership_id');
        if (!$ownershipId) {
            return [];
        }

        // Get editable fields from InvoiceEditRulesService
        $editableFields = [];
        if ($invoice) {
            $editRulesService = app(InvoiceEditRulesService::class);
            $editableFields = $editRulesService->getEditableFields($invoice);
        }

        $rules = [
            'contract_id' => [
                'sometimes',
                'integer',
                Rule::exists('contracts', 'id')->where(function ($query) use ($ownershipId) {
                    return $query->where('ownership_id', $ownershipId);
                }),
            ],
            // ownership_id cannot be changed via update (taken from middleware scope)
            'number' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('invoices', 'number')
                    ->where(function ($query) use ($ownershipId) {
                        return $query->where('ownership_id', $ownershipId);
                    })
                    ->ignore($invoiceId),
            ],
            'period_start' => ['sometimes', 'date'],
            'period_end' => [
                'sometimes',
                'date',
                'after_or_equal:period_start',
                function ($attribute, $value, $fail) use ($ownershipId, $invoiceId) {
                    // Custom validation: if contract_id exists, validate period
                    $contractId = request()->input('contract_id');
                    if ($contractId || request()->has('period_start')) {
                        $invoice = \App\Models\V1\Invoice\Invoice::find($invoiceId);
                        $contractId = $contractId ?? ($invoice?->contract_id);
                        
                        if ($contractId) {
                            $contract = Contract::where('id', $contractId)
                                ->where('ownership_id', $ownershipId)
                                ->first();
                                
                            if ($contract) {
                                try {
                                    $contractInvoiceService = app(ContractInvoiceService::class);
                                    $contractInvoiceService->validatePeriod($contract, [
                                        'start' => request()->input('period_start', $invoice?->period_start),
                                        'end' => $value,
                                    ], $invoice); // Exclude current invoice from overlap check
                                } catch (\Exception $e) {
                                    $fail($e->getMessage());
                                }
                            }
                        }
                    }
                },
            ],
            'due' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['nullable', 'string', 'max:50', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
        
        // If invoice requires approval after edit (based on setting), require reason
        if ($invoice) {
            $editRulesService = app(InvoiceEditRulesService::class);
            if ($editRulesService->requiresApprovalAfterEdit($invoice)) {
                $rules['edit_reason'] = ['required', 'string', 'max:500'];
            }
        }
        
        // If invoice is PARTIAL or PAID, restrict amount changes
        if ($invoice && in_array($invoice->status->value, ['partial', 'paid'])) {
            unset($rules['amount']);
            unset($rules['tax']);
            unset($rules['tax_rate']);
            unset($rules['total']);
        }
        
        // If invoice is not DRAFT or PENDING, restrict period changes
        if ($invoice && !in_array($invoice->status->value, ['draft', 'pending'])) {
            unset($rules['period_start']);
            unset($rules['period_end']);
        }
        
        // If invoice is not DRAFT or PENDING, restrict contract_id changes
        if ($invoice && !in_array($invoice->status->value, ['draft', 'pending'])) {
            unset($rules['contract_id']);
        }
        
        // Remove validation rules for non-editable fields based on InvoiceEditRulesService
        if ($invoice && !empty($editableFields)) {
            $allFields = array_keys($rules);
            foreach ($allFields as $field) {
                // Keep edit_reason if required, and keep validation rules that are not field-specific
                if ($field === 'edit_reason') {
                    continue;
                }
                // Remove field if it's not in editable fields list
                if (!in_array($field, $editableFields)) {
                    unset($rules[$field]);
                }
            }
        }
        
        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contract_id' => __('messages.attributes.contract_id'),
            'number' => __('messages.attributes.number'),
            'period_start' => __('messages.attributes.period_start'),
            'period_end' => __('messages.attributes.period_end'),
            'due' => __('messages.attributes.due'),
            'amount' => __('messages.attributes.amount'),
            'tax' => __('messages.attributes.tax'),
            'tax_rate' => __('messages.attributes.tax_rate'),
            'total' => __('messages.attributes.total'),
            'status' => __('messages.attributes.status'),
            'notes' => __('messages.attributes.notes'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contract_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.attributes.contract_id')]),
            'number.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.number')]),
            'period_start.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.period_start')]),
            'period_end.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.period_end')]),
            'due.date' => __('messages.validation.date', ['attribute' => __('messages.attributes.due')]),
            'amount.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.amount')]),
            'tax.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.tax')]),
            'tax_rate.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.tax_rate')]),
            'total.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.attributes.total')]),
            'status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.status')]),
        ];
    }
}

