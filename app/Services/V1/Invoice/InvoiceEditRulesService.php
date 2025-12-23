<?php

namespace App\Services\V1\Invoice;

use App\Enums\V1\Invoice\InvoiceStatus;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Auth\User;
use Illuminate\Support\Facades\Log;

class InvoiceEditRulesService
{
    public function __construct(
        private InvoiceSettingService $settings
    ) {}
    
    /**
     * Check if invoice can be edited.
     * 
     * @param Invoice $invoice
     * @param User $user
     * @return bool
     */
    public function canEdit(Invoice $invoice, User $user): bool
    {
        // Special cases: SENT, VIEWED, OVERDUE require special permission
        // Check permissions FIRST before checking allowsEditing()
        // Note: OVERDUE is treated the same as SENT/VIEWED - requires editSent permission and settings check
        if (in_array($invoice->status, [InvoiceStatus::SENT, InvoiceStatus::VIEWED, InvoiceStatus::OVERDUE])) {
            // Must have editSent permission
            if (!$user->can('invoices.editSent')) {
                return false;
            }
            // Check settings
            return $this->settings->canEditSent($invoice->ownership_id);
        }
        
        // PARTIAL status: can only edit notes and due date
        if ($invoice->status === InvoiceStatus::PARTIAL) {
            // Allow editing but validateEdit() will restrict to notes and due only
            return true;
        }
        
        // For other statuses, check if status allows editing
        if (!$invoice->status->allowsEditing()) {
            return false;
        }
        
        // Check settings for DRAFT
        if ($invoice->status === InvoiceStatus::DRAFT) {
            return $this->settings->canEditDraft($invoice->ownership_id);
        }
        
        // For PENDING, check settings (similar to DRAFT)
        if ($invoice->status === InvoiceStatus::PENDING) {
            return $this->settings->canEditDraft($invoice->ownership_id);
        }
        
        return false;
    }
    
    /**
     * Check if invoice can be deleted.
     * 
     * @param Invoice $invoice
     * @param User $user
     * @return bool
     */
    public function canDelete(Invoice $invoice, User $user): bool
    {
        if (!$invoice->status->allowsDeletion()) {
            return false;
        }
        
        if (!$user->can('invoices.delete')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if invoice requires approval after edit.
     * 
     * @param Invoice $invoice
     * @return bool
     */
    public function requiresApprovalAfterEdit(Invoice $invoice): bool
    {
        if ($invoice->status === InvoiceStatus::SENT || 
            $invoice->status === InvoiceStatus::VIEWED ||
            $invoice->status === InvoiceStatus::OVERDUE) {
            return $this->settings->requireApprovalAfterEdit($invoice->ownership_id);
        }
        
        return false;
    }
    
    /**
     * Check if invoice should be resent after edit.
     * 
     * @param Invoice $invoice
     * @return bool
     */
    public function shouldResendAfterEdit(Invoice $invoice): bool
    {
        if ($invoice->status === InvoiceStatus::SENT || 
            $invoice->status === InvoiceStatus::VIEWED ||
            $invoice->status === InvoiceStatus::OVERDUE) {
            return $this->settings->autoResendAfterEdit($invoice->ownership_id);
        }
        
        return false;
    }
    
    /**
     * Validate edit request.
     * 
     * @param Invoice $invoice
     * @param array $data
     * @param User $user
     * @return void
     * @throws \Exception
     */
    public function validateEdit(Invoice $invoice, array $data, User $user): void
    {
        if (!$this->canEdit($invoice, $user)) {
            throw new \Exception("Invoice cannot be edited in {$invoice->status->value} status");
        }
        
        // For PARTIAL status, only notes and due date can be changed
        if ($invoice->status === InvoiceStatus::PARTIAL) {
            $allowedFields = ['notes', 'due'];
            $changedFields = [];
            
            // Check which fields are being changed
            foreach ($data as $field => $value) {
                if (isset($invoice->$field) && $invoice->$field != $value) {
                    $changedFields[] = $field;
                }
            }
            
            $forbiddenFields = array_diff($changedFields, $allowedFields);
            if (!empty($forbiddenFields)) {
                throw new \Exception("Cannot change fields for partially paid invoice. Only 'notes' and 'due' can be changed. Attempted to change: " . implode(', ', $forbiddenFields));
            }
            
            // If only allowed fields are changed, allow it
            return;
        }
        
        // Check if amount can be changed
        if (isset($data['amount']) && $data['amount'] != $invoice->amount) {
            if ($invoice->status === InvoiceStatus::PAID) {
                throw new \Exception("Cannot change amount for paid invoice");
            }
        }
        
        // Check if period can be changed
        if (isset($data['period_start']) || isset($data['period_end'])) {
            if ($invoice->status !== InvoiceStatus::DRAFT && 
                $invoice->status !== InvoiceStatus::PENDING) {
                throw new \Exception("Cannot change period for {$invoice->status->value} invoice");
            }
        }
        
        // Check if contract_id can be changed
        if (isset($data['contract_id']) && $data['contract_id'] != $invoice->contract_id) {
            if ($invoice->status !== InvoiceStatus::DRAFT && 
                $invoice->status !== InvoiceStatus::PENDING) {
                throw new \Exception("Cannot change contract for {$invoice->status->value} invoice");
            }
        }
    }
    
    /**
     * Get fields that can be edited for a specific status.
     * 
     * @param Invoice $invoice
     * @return array
     */
    public function getEditableFields(Invoice $invoice): array
    {
        $editableFields = [];
        
        switch ($invoice->status) {
            case InvoiceStatus::DRAFT:
            case InvoiceStatus::PENDING:
                $editableFields = [
                    'contract_id',
                    'number',
                    'period_start',
                    'period_end',
                    'due',
                    'amount',
                    'tax',
                    'tax_rate',
                    'total',
                    'notes',
                ];
                break;
                
            case InvoiceStatus::SENT:
            case InvoiceStatus::VIEWED:
            case InvoiceStatus::OVERDUE:
                $editableFields = [
                    'due',
                    'notes',
                ];
                // Amount can be changed only if not paid
                if ($invoice->status !== InvoiceStatus::PARTIAL && 
                    $invoice->status !== InvoiceStatus::PAID) {
                    $editableFields[] = 'amount';
                    $editableFields[] = 'tax';
                    $editableFields[] = 'tax_rate';
                    $editableFields[] = 'total';
                }
                break;
                
            case InvoiceStatus::PARTIAL:
                $editableFields = [
                    'notes',
                    'due',
                ];
                break;
                
            case InvoiceStatus::PAID:
            case InvoiceStatus::CANCELLED:
            case InvoiceStatus::REFUNDED:
                // No editable fields
                break;
        }
        
        return $editableFields;
    }
}

