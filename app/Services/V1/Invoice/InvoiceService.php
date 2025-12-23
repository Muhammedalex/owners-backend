<?php

namespace App\Services\V1\Invoice;

use App\Enums\V1\Invoice\InvoiceStatus;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Invoice\InvoiceItem;
use App\Models\V1\Contract\Contract;
use App\Repositories\V1\Invoice\Interfaces\InvoiceRepositoryInterface;
use App\Services\V1\Document\DocumentService;
use App\Services\V1\Invoice\InvoiceSettingService;
use App\Services\V1\Invoice\InvoiceEditRulesService;
use App\Services\V1\Notification\NotificationService;
use App\Services\V1\Notification\SmsService;
use App\Services\V1\Mail\OwnershipMailService;
use App\Mail\V1\Invoice\InvoiceSentMail;
use App\Models\V1\Invoice\InvoiceChangeLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private DocumentService $documentService,
        private ContractInvoiceService $contractInvoiceService,
        private InvoiceSettingService $invoiceSettings,
        private InvoiceEditRulesService $editRulesService,
        private NotificationService $notificationService,
        private SmsService $smsService,
        private OwnershipMailService $mailService,
    ) {}

    /**
     * Get all invoices with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginate($perPage, $filters);
    }

    /**
     * Get all invoices.
     */
    public function all(array $filters = []): Collection
    {
        return $this->invoiceRepository->all($filters);
    }

    /**
     * Find invoice by ID.
     */
    public function find(int $id): ?Invoice
    {
        return $this->invoiceRepository->find($id);
    }

    /**
     * Find invoice by UUID.
     */
    public function findByUuid(string $uuid): ?Invoice
    {
        return $this->invoiceRepository->findByUuid($uuid);
    }

    /**
     * Find invoice by number.
     */
    public function findByNumber(string $number): ?Invoice
    {
        return $this->invoiceRepository->findByNumber($number);
    }

    /**
     * Create a new invoice (general - for any type).
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // If linked to contract, validate period and use ContractInvoiceService logic
            if (isset($data['contract_id']) && $data['contract_id']) {
                $contract = Contract::findOrFail($data['contract_id']);
                $ownershipId = $contract->ownership_id;
                
                // Check if manual creation is allowed when auto-generation is enabled
                $mode = $this->invoiceSettings->getAutoGenerationMode($ownershipId);
                if ($mode === 'system_only') {
                    $allowManual = $this->invoiceSettings->allowManualWhenAuto($ownershipId);
                    if (!$allowManual) {
                        throw new \Exception('Manual invoice creation is disabled. Auto-generation is enabled for this ownership.');
                    }
                }
                // Note: In 'mixed' mode, manual creation is allowed and overlaps are checked by validatePeriod below
                
                // Validate period if period dates are provided
                // This checks for overlaps in all modes (including mixed mode)
                if (isset($data['period_start']) && isset($data['period_end'])) {
                    $this->contractInvoiceService->validatePeriod($contract, [
                        'start' => $data['period_start'],
                        'end' => $data['period_end'],
                    ], null); // No invoice to exclude for new invoices
                }
                
                // لا نضيف ضريبة - العقد يحتوي على VAT
                $data['tax_from_contract'] = true;
                $data['tax'] = null;
                $data['tax_rate'] = null;
                
                // Calculate amount from contract based on period length if not provided
                if (!isset($data['amount']) && isset($data['period_start']) && isset($data['period_end'])) {
                    $data['amount'] = $this->contractInvoiceService->calculateAmountFromContract($contract, [
                        'start' => $data['period_start'],
                        'end' => $data['period_end'],
                    ]);
                }
                
                $data['total'] = $data['amount'] ?? 0; // بدون ضريبة إضافية
            } else {
                // فاتورة مستقلة - التحقق من البيانات المطلوبة
                $this->validateStandaloneInvoice($data);
                
                // فاتورة مستقلة - قد تحتاج ضريبة
                $data['tax_from_contract'] = false;
                
                // حساب الضريبة إذا كانت موجودة
                if (isset($data['tax_rate']) && $data['tax_rate'] > 0) {
                    if (!isset($data['tax'])) {
                        $data['tax'] = $data['amount'] * ($data['tax_rate'] / 100);
                    }
                    $data['total'] = $data['amount'] + $data['tax'];
                } else {
                    $data['tax'] = null;
                    $data['tax_rate'] = null;
                    $data['total'] = $data['amount'] ?? 0;
                }
            }

            // Set generated_at if not provided
            if (!isset($data['generated_at'])) {
                $data['generated_at'] = now();
            }

            return $this->invoiceRepository->create($data);
        });
    }

    /**
     * Update invoice.
     */
    public function update(Invoice $invoice, array $data, $user = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $user) {
            // Get user from parameter or auth
            $currentUser = $user ?? Auth::user();
            
            // Validate edit rules
            $this->editRulesService->validateEdit($invoice, $data, $currentUser);
            
            // Store old values and status for audit
            $oldValues = $invoice->toArray();
            $oldStatus = $invoice->status;
            
            // إذا كانت مرتبطة بعقد ولا تزال مرتبطة
            if ($invoice->isLinkedToContract() && isset($data['contract_id']) && $data['contract_id']) {
                $contract = Contract::findOrFail($data['contract_id']);
                
                // Validate period if period dates are being updated
                if (isset($data['period_start']) && isset($data['period_end'])) {
                    $this->contractInvoiceService->validatePeriod($contract, [
                        'start' => $data['period_start'],
                        'end' => $data['period_end'],
                    ], $invoice); // Exclude current invoice from overlap check
                }
                
                // لا نضيف ضريبة - العقد يحتوي على VAT
                $data['tax_from_contract'] = true;
                $data['tax'] = null;
                $data['tax_rate'] = null;
                
                // Calculate amount if period changed or contract changed
                if ((isset($data['period_start']) && isset($data['period_end'])) || 
                    (isset($data['contract_id']) && $data['contract_id'] != $invoice->contract_id)) {
                    $periodStart = $data['period_start'] ?? $invoice->period_start;
                    $periodEnd = $data['period_end'] ?? $invoice->period_end;
                    
                    if (!isset($data['amount'])) {
                        $data['amount'] = $this->contractInvoiceService->calculateAmountFromContract($contract, [
                            'start' => $periodStart,
                            'end' => $periodEnd,
                        ]);
                    }
                }
                
                $data['total'] = $data['amount'] ?? $invoice->amount;
            } elseif (!$invoice->isLinkedToContract() && (!isset($data['contract_id']) || !$data['contract_id'])) {
                // فاتورة مستقلة - تحسب الضريبة
                $data['tax_from_contract'] = false;
                
                // Recalculate tax if amount or tax_rate changed
                if (isset($data['amount']) || isset($data['tax_rate'])) {
                    $amount = $data['amount'] ?? $invoice->amount;
                    $taxRate = $data['tax_rate'] ?? $invoice->tax_rate;
                    if ($taxRate && $taxRate > 0) {
                        $data['tax'] = $amount * ($taxRate / 100);
                        $data['total'] = $amount + $data['tax'];
                    } else {
                        $data['tax'] = null;
                        $data['tax_rate'] = null;
                        $data['total'] = $amount;
                    }
                } else {
                    // Recalculate total if amount or tax changed
                    if (isset($data['amount']) || isset($data['tax'])) {
                        $amount = $data['amount'] ?? $invoice->amount;
                        $tax = $data['tax'] ?? $invoice->tax ?? 0;
                        $data['total'] = $amount + $tax;
                    }
                }
            }

            // Update invoice
            $updatedInvoice = $this->invoiceRepository->update($invoice, $data);
            
            // Log changes (pass oldStatus for audit trail)
            $this->logInvoiceChanges($updatedInvoice, $oldValues, $currentUser, $data['edit_reason'] ?? null, $oldStatus);
            
            // Check if requires approval (check old status, not new status)
            // We need to check the old status because requiresApprovalAfterEdit checks the current status
            if (in_array($oldStatus, [InvoiceStatus::SENT, InvoiceStatus::VIEWED, InvoiceStatus::OVERDUE])) {
                if ($this->editRulesService->requiresApprovalAfterEdit($invoice->fresh())) {
                    $updatedInvoice->transitionTo(InvoiceStatus::PENDING, 'Requires approval after edit', $currentUser->id);
                }
            }
            
            // Check if should resend
            if ($this->editRulesService->shouldResendAfterEdit($updatedInvoice)) {
                // Resend invoice by marking it as sent again
                $this->markAsSent($updatedInvoice);
            }
            
            return $updatedInvoice;
        });
    }
    
    /**
     * Log invoice changes.
     */
    private function logInvoiceChanges(Invoice $invoice, array $oldValues, $user, ?string $reason = null, ?InvoiceStatus $oldStatus = null): void
    {
        $newValues = $invoice->fresh()->toArray();
        $changedFields = [];
        
        // Compare old and new values
        foreach ($oldValues as $key => $oldValue) {
            if (isset($newValues[$key]) && $newValues[$key] != $oldValue) {
                $changedFields[] = $key;
            }
        }
        
        // Only log if there are actual changes
        if (!empty($changedFields)) {
            InvoiceChangeLog::create([
                'invoice_id' => $invoice->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'changed_fields' => $changedFields,
                'reason' => $reason,
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);
        }
    }

    /**
     * Delete invoice.
     */
    public function delete(Invoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            // Load relationships
            $invoice->load(['documents']);

            // Delete all documents
            foreach ($invoice->documents as $document) {
                $this->documentService->delete($document);
            }

            return $this->invoiceRepository->delete($invoice);
        });
    }

    /**
     * Update invoice status.
     */
    public function updateStatus(Invoice $invoice, InvoiceStatus $newStatus, ?string $reason = null, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $newStatus, $reason, $userId) {
            // Get user for permission checks
            $user = $userId ? \App\Models\V1\Auth\User::find($userId) : Auth::user();
            
            if (!$invoice->canTransitionTo($newStatus, $user)) {
                throw new \Exception("Cannot transition from {$invoice->status->value} to {$newStatus->value}");
            }
            
            $invoice->transitionTo($newStatus, $reason, $userId ?? Auth::id());
            $invoice = $invoice->fresh();
            
            // If status changed to SENT, send notifications
            if ($newStatus === InvoiceStatus::SENT) {
                // Load relationships needed for notifications
                $invoice->loadMissing(['contract.tenant.user', 'ownership']);
                $this->sendInvoiceNotifications($invoice);
            }
            
            return $invoice;
        });
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        return $this->updateStatus($invoice, InvoiceStatus::PAID, 'Invoice marked as paid');
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice): Invoice
    {
        return $this->updateStatus($invoice, InvoiceStatus::SENT, 'Invoice sent to tenant');
    }

    /**
     * Generate invoice from contract (supports multiple units).
     * 
     * This method is kept for backward compatibility.
     * It now uses ContractInvoiceService internally.
     *
     * @param Contract $contract
     * @param array{start:string,end:string,due:string,number?:string,status?:string,generated_by?:int,generated_at?:string} $period
     */
    public function generateFromContract(Contract $contract, array $period): Invoice
    {
        // Use ContractInvoiceService to generate invoice
        $invoice = $this->contractInvoiceService->generateFromContract($contract, $period);

        // Create invoice items
        $this->createInvoiceItemsForContract($invoice, $contract);

        return $invoice;
    }

    /**
     * Create invoice items for a contract (single or multiple units).
     */
    public function createInvoiceItemsForContract(Invoice $invoice, Contract $contract): void
    {
        $units = $contract->relationLoaded('units') ? $contract->units : $contract->units()->get();

        if ($units->isEmpty()) {
            // Fallback: single item based on contract base_rent
            $baseRent = (float) ($contract->base_rent ?? 0);
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'rent',
                'description' => 'Rent for contract #' . $contract->number,
                'quantity' => 1,
                'unit_price' => $baseRent,
                'total' => $baseRent,
            ]);
            return;
        }

        // If multiple units: one item per unit (using pivot rent_amount when available)
        foreach ($units as $unit) {
            $pivotRent = $unit->pivot?->rent_amount;
            // Fallback to base_rent divided by units count if pivot rent not available
            $fallbackRent = $pivotRent !== null 
                ? (float) $pivotRent 
                : (float) (($contract->base_rent ?? 0) / max($units->count(), 1));
            $unitRent = $pivotRent !== null ? (float) $pivotRent : $fallbackRent;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'rent',
                'description' => 'Rent for unit ' . ($unit->number ?? $unit->id) . ' for period ' . $invoice->period_start . ' to ' . $invoice->period_end,
                'quantity' => 1,
                'unit_price' => $unitRent,
                'total' => $unitRent,
            ]);
        }
    }

    /**
     * Validate standalone invoice data.
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    private function validateStandaloneInvoice(array $data): void
    {
        // amount مطلوب للفواتير المستقلة
        if (!isset($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception('Amount is required for standalone invoices');
        }
        
        // ownership_id مطلوب
        if (!isset($data['ownership_id'])) {
            throw new \Exception('Ownership ID is required for standalone invoices');
        }
    }

    /**
     * Send invoice notifications (email, SMS, system notification) when invoice is sent.
     *
     * @param Invoice $invoice
     * @return void
     */
    protected function sendInvoiceNotifications(Invoice $invoice): void
    {
        $logger = Log::channel('invoices');
        $ownershipId = $invoice->ownership_id;
        $contract = $invoice->contract;
        
        if (!$contract) {
            $logger->debug('Cannot send invoice notifications - contract not found (standalone invoice)', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
            ]);
            return; // Standalone invoices don't have tenants, skip notifications
        }

        $tenant = $contract->tenant;
        if (!$tenant) {
            $logger->warning('Cannot send invoice notifications - tenant not found', [
                'invoice_id' => $invoice->id,
                'contract_id' => $contract->id,
            ]);
            return;
        }

        $tenantUser = $tenant->user;
        if (!$tenantUser) {
            $logger->warning('Cannot send invoice notifications - tenant user not found', [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
            ]);
            return;
        }

        // Send Email
        if ($this->invoiceSettings->shouldSendEmail($ownershipId)) {
            try {
                if ($tenantUser->email) {
                    $mailable = new InvoiceSentMail($invoice);
                    $this->mailService->sendForOwnership($ownershipId, $tenantUser->email, $mailable);
                    
                    $logger->info('Invoice email sent', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number,
                        'email' => $tenantUser->email,
                    ]);
                } else {
                    $logger->warning('Cannot send invoice email - tenant user has no email', [
                        'invoice_id' => $invoice->id,
                        'tenant_user_id' => $tenantUser->id,
                    ]);
                }
            } catch (\Throwable $e) {
                $logger->error('Failed to send invoice email', [
                    'invoice_id' => $invoice->id,
                    'email' => $tenantUser->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Send SMS
        if ($this->invoiceSettings->shouldSendSms($ownershipId)) {
            try {
                if ($tenantUser->phone) {
                    $message = $this->formatInvoiceSmsMessage($invoice);
                    $this->smsService->sendMessage($tenantUser->phone, $message, $ownershipId);
                    
                    $logger->info('Invoice SMS sent', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number,
                        'phone' => $tenantUser->phone,
                    ]);
                } else {
                    $logger->warning('Cannot send invoice SMS - tenant user has no phone', [
                        'invoice_id' => $invoice->id,
                        'tenant_user_id' => $tenantUser->id,
                    ]);
                }
            } catch (\Throwable $e) {
                $logger->error('Failed to send invoice SMS', [
                    'invoice_id' => $invoice->id,
                    'phone' => $tenantUser->phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Send System Notification
        if ($this->invoiceSettings->shouldSendNotification($ownershipId)) {
            try {
                $this->notificationService->create([
                    'user_id' => $tenantUser->id,
                    'type' => 'info',
                    'title' => __('notifications.invoice.sent.title', [
                        'number' => $invoice->number,
                    ]),
                    'message' => __('notifications.invoice.sent.message', [
                        'number' => $invoice->number,
                        'total' => number_format($invoice->total, 2),
                        'due_date' => $invoice->due->format('Y-m-d'),
                    ]),
                    'category' => 'invoice',
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_uuid' => $invoice->uuid,
                        'invoice_number' => $invoice->number,
                        'action_url' => '/invoices/' . $invoice->uuid,
                    ],
                    'read_at' => null,
                ]);

                $logger->info('Invoice system notification created', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'user_id' => $tenantUser->id,
                ]);
            } catch (\Throwable $e) {
                $logger->error('Failed to create invoice system notification', [
                    'invoice_id' => $invoice->id,
                    'user_id' => $tenantUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Format SMS message for invoice.
     *
     * @param Invoice $invoice
     * @return string
     */
    protected function formatInvoiceSmsMessage(Invoice $invoice): string
    {
        $ownershipName = $invoice->ownership->name ?? 'Property Management';
        return __('sms.invoice.sent', [
            'ownership' => $ownershipName,
            'number' => $invoice->number,
            'total' => number_format($invoice->total, 2),
            'due_date' => $invoice->due->format('Y-m-d'),
        ]);
    }
}

