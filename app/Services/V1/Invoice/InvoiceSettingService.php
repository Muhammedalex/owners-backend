<?php

namespace App\Services\V1\Invoice;

use App\Services\V1\Setting\SystemSettingService;

/**
 * Invoice Settings Service
 * 
 * Provides typed access to invoice-related system settings
 * with proper fallback values and type casting.
 */
class InvoiceSettingService
{
    public function __construct(
        private SystemSettingService $systemSettingService
    ) {}

    /**
     * Get auto generation mode.
     * 
     * @param int|null $ownershipId
     * @return string 'disabled'|'system_only'|'user_only'|'mixed'
     */
    public function getAutoGenerationMode(?int $ownershipId = null): string
    {
        return $this->systemSettingService->getValue(
            'invoice_auto_generation_mode',
            $ownershipId,
            'disabled'
        ) ?: 'disabled';
    }
    
    /**
     * Get generation days before due date.
     * 
     * @param int|null $ownershipId
     * @return int
     */
    public function getGenerationDaysBeforeDue(?int $ownershipId = null): int
    {
        return (int) $this->systemSettingService->getValue(
            'invoice_generation_days_before_due',
            $ownershipId,
            5
        ) ?: 5;
    }
    
    /**
     * Get days after period start for due date calculation.
     * 
     * @param int|null $ownershipId
     * @return int
     */
    public function getDueDaysAfterPeriodStart(?int $ownershipId = null): int
    {
        // Try new setting first
        $value = $this->systemSettingService->getValue(
            'invoice_due_days_after_period_start',
            $ownershipId,
            null
        );
        
        if ($value !== null) {
            return (int) $value ?: 10;
        }
        
        // Fallback to old setting or default
        return (int) $this->systemSettingService->getValue(
            'invoice_due_days_after_period',
            $ownershipId,
            10
        ) ?: 10;
    }
    
    /**
     * Check if overlapping periods should be prevented.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function preventOverlappingPeriods(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_prevent_overlapping_periods',
            $ownershipId,
            true
        );
    }
    
    /**
     * Check if manual invoices are allowed when auto-generation is enabled.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function allowManualWhenAuto(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_allow_manual_when_auto',
            $ownershipId,
            true
        );
    }
    
    /**
     * Check if draft invoices can be edited.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function canEditDraft(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_allow_edit_draft',
            $ownershipId,
            true
        );
    }
    
    /**
     * Check if sent invoices can be edited.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function canEditSent(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_allow_edit_sent',
            $ownershipId,
            false
        );
    }
    
    /**
     * Check if approval is required after editing sent invoices.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function requireApprovalAfterEdit(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_require_approval_after_edit',
            $ownershipId,
            true
        );
    }
    
    /**
     * Check if invoice should be auto-resent after editing.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function autoResendAfterEdit(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_auto_resend_after_edit',
            $ownershipId,
            false
        );
    }
    
    /**
     * Get invoice status workflow mode.
     * 
     * @param int|null $ownershipId
     * @return string 'strict'|'flexible'
     */
    public function getStatusWorkflow(?int $ownershipId = null): string
    {
        return $this->systemSettingService->getValue(
            'invoice_status_workflow',
            $ownershipId,
            'strict'
        ) ?: 'strict';
    }
    
    /**
     * Check if invoice should be auto-marked as paid.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function autoMarkAsPaid(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_auto_mark_paid',
            $ownershipId,
            false
        );
    }
    
    /**
     * Check if partial payments are allowed.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function allowPartialPayment(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_allow_partial_payment',
            $ownershipId,
            true
        );
    }
    
    /**
     * Get overdue handling method.
     * 
     * @param int|null $ownershipId
     * @return string 'notify'|'penalty'|'block'
     */
    public function getOverdueHandling(?int $ownershipId = null): string
    {
        return $this->systemSettingService->getValue(
            'invoice_overdue_handling',
            $ownershipId,
            'notify'
        ) ?: 'notify';
    }
    
    /**
     * Get overdue penalty rate.
     * 
     * @param int|null $ownershipId
     * @return float
     */
    public function getOverduePenaltyRate(?int $ownershipId = null): float
    {
        return (float) $this->systemSettingService->getValue(
            'invoice_overdue_penalty_rate',
            $ownershipId,
            0
        ) ?: 0;
    }
    
    /**
     * Check if collector system is enabled.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isCollectorSystemEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'collector_system_enabled',
            $ownershipId,
            true
        );
    }
    
    /**
     * Check if collectors can see all tenants.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function collectorsCanSeeAllTenants(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'collector_see_all_tenants',
            $ownershipId,
            false
        );
    }
    
    /**
     * Get default collector assignment method.
     * 
     * @param int|null $ownershipId
     * @return string 'manual'|'auto'|'round_robin'
     */
    public function getCollectorAssignmentMethod(?int $ownershipId = null): string
    {
        return $this->systemSettingService->getValue(
            'collector_default_assignment',
            $ownershipId,
            'manual'
        ) ?: 'manual';
    }

    /**
     * Get default invoice status when generated by system.
     * 
     * @param int|null $ownershipId
     * @return string 'draft'|'pending'|'sent'
     */
    public function getDefaultInvoiceStatus(?int $ownershipId = null): string
    {
        $status = $this->systemSettingService->getValue(
            'invoice_default_status',
            $ownershipId,
            'draft'
        ) ?: 'draft';
        
        // Validate that status is one of the allowed initial statuses
        // Only draft, pending, or sent are allowed as default statuses for system-generated invoices
        $allowedStatuses = ['draft', 'pending', 'sent'];
        if (!in_array($status, $allowedStatuses)) {
            \Illuminate\Support\Facades\Log::warning('Invalid invoice_default_status setting value', [
                'ownership_id' => $ownershipId,
                'invalid_status' => $status,
                'fallback_to' => 'draft',
            ]);
            return 'draft'; // Fallback to draft if invalid
        }
        
        return $status;
    }

    /**
     * Check if invoice should be sent via email when status is sent.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function shouldSendEmail(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_send_email',
            $ownershipId,
            true
        );
    }

    /**
     * Check if invoice should be sent via SMS when status is sent.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function shouldSendSms(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_send_sms',
            $ownershipId,
            false
        );
    }

    /**
     * Check if invoice should send system notification when status is sent.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function shouldSendNotification(?int $ownershipId = null): bool
    {
        return (bool) $this->systemSettingService->getValue(
            'invoice_send_notification',
            $ownershipId,
            true
        );
    }
}

