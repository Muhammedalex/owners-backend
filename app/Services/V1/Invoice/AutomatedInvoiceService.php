<?php

namespace App\Services\V1\Invoice;

use App\Models\V1\Contract\Contract;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Ownership\Ownership;
use App\Services\V1\Notification\NotificationService;
use App\Services\V1\Notification\SmsService;
use App\Services\V1\Mail\OwnershipMailService;
use App\Mail\V1\Invoice\InvoiceSentMail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutomatedInvoiceService
{
    public function __construct(
        private InvoiceService $invoiceService,
        private ContractInvoiceService $contractInvoiceService,
        private InvoiceSettingService $invoiceSettings,
        private NotificationService $notificationService,
        private SmsService $smsService,
        private OwnershipMailService $mailService,
    ) {}

    /**
     * Generate draft invoices for all contracts that are due for invoicing.
     * 
     * Processes all ownerships and respects auto-generation settings.
     *
     * @return array{generated:array,skipped:array,total_generated:int}
     */
    public function generateInvoicesForDueContracts(): array
    {
        $logger = Log::channel('invoices');
        $logger->info('=== Starting Invoice Generation Process ===', [
            'timestamp' => now()->toDateTimeString(),
        ]);

        $ownerships = \App\Models\V1\Ownership\Ownership::all();
        $logger->info('Found ownerships to process', [
            'total_ownerships' => $ownerships->count(),
        ]);

        $results = [
            'generated' => [],
            'skipped' => [],
            'total_generated' => 0,
        ];

        foreach ($ownerships as $ownership) {
            $logger->info('Processing ownership', [
                'ownership_id' => $ownership->id,
                'ownership_name' => $ownership->name ?? 'N/A',
            ]);

            // Check if auto-generation is enabled for this ownership
            $mode = $this->invoiceSettings->getAutoGenerationMode($ownership->id);
            $logger->debug('Checking auto-generation mode', [
                'ownership_id' => $ownership->id,
                'mode' => $mode,
            ]);

            if ($mode === 'disabled' || $mode === 'user_only') {
                $logger->info('Skipping ownership - auto-generation disabled or user_only', [
                    'ownership_id' => $ownership->id,
                    'mode' => $mode,
                ]);
                continue; // Skip this ownership
            }

            $contracts = $this->getContractsDueForInvoicing($ownership->id);
            $logger->info('Found contracts for invoicing', [
                'ownership_id' => $ownership->id,
                'contracts_count' => $contracts->count(),
            ]);
            
            foreach ($contracts as $contract) {
                $logger->info('Processing contract', [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->number ?? 'N/A',
                    'payment_frequency' => $contract->payment_frequency,
                    'contract_start' => $contract->start,
                    'contract_end' => $contract->end,
                    'contract_status' => $contract->status,
                ]);

                try {
                    // Generate all missing invoices until today (not just one)
                    $maxIterations = 100; // Safety limit to prevent infinite loops
                    $iterations = 0;
                    $contractInvoicesGenerated = 0;
                    
                    while ($iterations < $maxIterations) {
                        $logger->debug('Attempting to generate invoice', [
                            'contract_id' => $contract->id,
                            'iteration' => $iterations + 1,
                            'max_iterations' => $maxIterations,
                        ]);

                        $invoice = $this->generateInvoiceForContract($contract);
                        if ($invoice) {
                            $logger->info('Invoice generated successfully', [
                                'invoice_id' => $invoice->id,
                                'invoice_uuid' => $invoice->uuid,
                                'invoice_number' => $invoice->number,
                                'contract_id' => $contract->id,
                                'ownership_id' => $ownership->id,
                                'period_start' => $invoice->period_start,
                                'period_end' => $invoice->period_end,
                                'due_date' => $invoice->due,
                                'total' => $invoice->total,
                                'status' => $invoice->status,
                            ]);

                            $results['generated'][] = [
                                'invoice_id' => $invoice->id,
                                'contract_id' => $contract->id,
                                'ownership_id' => $ownership->id,
                            ];
                            $results['total_generated']++;
                            $iterations++;
                            $contractInvoicesGenerated++;
                            
                            // Refresh contract to get updated invoices relationship
                            $contract->refresh();
                        } else {
                            $logger->debug('No invoice generated for this iteration', [
                                'contract_id' => $contract->id,
                                'iteration' => $iterations + 1,
                                'reason' => 'No more invoices to generate or conditions not met',
                            ]);
                            // No more invoices to generate for this contract
                            break;
                        }
                    }

                    if ($contractInvoicesGenerated > 0) {
                        $logger->info('Completed contract processing', [
                            'contract_id' => $contract->id,
                            'invoices_generated' => $contractInvoicesGenerated,
                        ]);
                    } else {
                        $logger->debug('No invoices generated for contract', [
                            'contract_id' => $contract->id,
                            'reason' => 'All conditions checked, no invoices needed',
                        ]);
                    }
                } catch (\Throwable $e) {
                    $logger->error('Failed to auto-generate invoice for contract', [
                        'contract_id' => $contract->id,
                        'ownership_id' => $ownership->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);

                    $results['skipped'][] = [
                        'contract_id' => $contract->id,
                        'ownership_id' => $ownership->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        $logger->info('=== Invoice Generation Process Completed ===', [
            'total_generated' => $results['total_generated'],
            'total_skipped' => count($results['skipped']),
            'timestamp' => now()->toDateTimeString(),
        ]);

        return $results;
    }

    /**
     * Generate a single draft invoice for a specific contract if it is due.
     *
     * Returns the created invoice, or null if no invoice should be generated (already invoiced / out of range).
     */
    public function generateInvoiceForContract(Contract $contract): ?Invoice
    {
        $logger = Log::channel('invoices');

        // Check if auto-generation is enabled for this ownership
        $mode = $this->invoiceSettings->getAutoGenerationMode($contract->ownership_id);
        if ($mode === 'disabled' || $mode === 'user_only') {
            $logger->debug('Auto-generation disabled or user_only', [
                'contract_id' => $contract->id,
                'ownership_id' => $contract->ownership_id,
                'mode' => $mode,
            ]);
            return null;
        }

        // Ensure contract is active
        if ($contract->status !== 'active') {
            $logger->debug('Contract is not active', [
                'contract_id' => $contract->id,
                'status' => $contract->status,
            ]);
            return null;
        }

        // Calculate next period using ContractInvoiceService
        $period = $this->contractInvoiceService->calculateNextPeriod($contract);
        if ($period === null) {
            $logger->debug('No more periods to generate', [
                'contract_id' => $contract->id,
                'contract_end' => $contract->end,
            ]);
            return null; // No more periods
        }

        $logger->debug('Calculated next period', [
            'contract_id' => $contract->id,
            'period_start' => $period['start']->toDateString(),
            'period_end' => $period['end']->toDateString(),
            'due_date' => $period['due']->toDateString(),
        ]);

        $today = Carbon::today();
        $ownershipId = $contract->ownership_id;

        // Check if we should generate for this period based on generation_days_before_due setting
        $generationDaysBeforeDue = $this->invoiceSettings->getGenerationDaysBeforeDue($ownershipId);
        $dueDate = $period['due'];
        $earliestGenerationDate = $dueDate->copy()->subDays($generationDaysBeforeDue);
        
        $logger->debug('Checking generation timing', [
            'contract_id' => $contract->id,
            'period_start' => $period['start']->toDateString(),
            'period_end' => $period['end']->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'generation_days_before_due' => $generationDaysBeforeDue,
            'earliest_generation_date' => $earliestGenerationDate->toDateString(),
            'today' => $today->toDateString(),
        ]);

        // Only generate when:
        // 1. Period start is in the past or today (catch-up mode), OR
        // 2. Today is on or after the earliest generation date (based on generation_days_before_due)
        if ($period['start']->isFuture() && $today->lt($earliestGenerationDate)) {
            $logger->debug('Period start is in the future and not yet time to generate', [
                'contract_id' => $contract->id,
                'period_start' => $period['start']->toDateString(),
                'earliest_generation_date' => $earliestGenerationDate->toDateString(),
                'today' => $today->toDateString(),
            ]);
            return null;
        }
        // Past/current periods will be generated (catch-up mode)
        // Future periods will be generated if today >= earliest_generation_date

        // Check for overlapping invoices
        if ($this->invoiceSettings->preventOverlappingPeriods($ownershipId)) {
            if ($this->contractInvoiceService->hasOverlappingInvoice($contract, [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
            ])) {
                $logger->debug('Period overlaps with existing invoice', [
                    'contract_id' => $contract->id,
                    'period_start' => $period['start']->toDateString(),
                    'period_end' => $period['end']->toDateString(),
                ]);
                return null; // Period overlaps with existing invoice
            }
        }

        // Get default status from settings
        $defaultStatus = $this->invoiceSettings->getDefaultInvoiceStatus($contract->ownership_id);
        
        // Validate status using enum
        $statusEnum = \App\Enums\V1\Invoice\InvoiceStatus::tryFrom($defaultStatus);
        if (!$statusEnum) {
            $logger->error('Invalid default invoice status from settings', [
                'contract_id' => $contract->id,
                'invalid_status' => $defaultStatus,
                'fallback_to' => 'draft',
            ]);
            $defaultStatus = 'draft'; // Fallback to draft
            $statusEnum = \App\Enums\V1\Invoice\InvoiceStatus::DRAFT;
        }
        
        // Ensure status is one of the allowed initial statuses (draft, pending, sent)
        $allowedInitialStatuses = [
            \App\Enums\V1\Invoice\InvoiceStatus::DRAFT,
            \App\Enums\V1\Invoice\InvoiceStatus::PENDING,
            \App\Enums\V1\Invoice\InvoiceStatus::SENT,
        ];
        if (!in_array($statusEnum, $allowedInitialStatuses)) {
            $logger->error('Default invoice status is not allowed for initial creation', [
                'contract_id' => $contract->id,
                'invalid_status' => $defaultStatus,
                'allowed_statuses' => array_map(fn($s) => $s->value, $allowedInitialStatuses),
                'fallback_to' => 'draft',
            ]);
            $defaultStatus = 'draft'; // Fallback to draft
        }
        
        $logger->debug('Using default invoice status', [
            'contract_id' => $contract->id,
            'default_status' => $defaultStatus,
        ]);

        // Build payload for ContractInvoiceService::generateFromContract
        $payload = [
            'start' => $period['start']->toDateString(),
            'end' => $period['end']->toDateString(),
            'due' => $period['due']->toDateString(),
            'status' => $defaultStatus,    // Use default status from settings (validated)
            'generated_by' => null,        // System
            'generated_at' => now(),
        ];

        $logger->debug('Generating invoice with payload', [
            'contract_id' => $contract->id,
            'payload' => $payload,
        ]);

        return DB::transaction(function () use ($contract, $payload, $logger) {
            // Use ContractInvoiceService to generate invoice
            $invoice = $this->contractInvoiceService->generateFromContract($contract, $payload);
            
            $logger->debug('Invoice created, generating items', [
                'invoice_id' => $invoice->id,
                'contract_id' => $contract->id,
            ]);
            
            // Create invoice items
            $this->invoiceService->createInvoiceItemsForContract($invoice, $contract);
            
            $logger->debug('Invoice items created', [
                'invoice_id' => $invoice->id,
                'items_count' => $invoice->items()->count(),
            ]);

            // If status is 'sent', send notifications
            if ($invoice->status->value === 'sent') {
                $this->sendInvoiceNotifications($invoice, $logger);
            }
            
            return $invoice;
        });
    }

    /**
     * Get all active contracts that are candidates for invoicing.
     *
     * @param int|null $ownershipId Filter by ownership (optional)
     */
    protected function getContractsDueForInvoicing(?int $ownershipId = null): Collection
    {
        $logger = Log::channel('invoices');
        $today = Carbon::today();

        $query = Contract::query()
            ->where('status', 'active')
            ->whereDate('start', '<=', $today)
            ->whereDate('end', '>=', $today);

        if ($ownershipId) {
            $query->where('ownership_id', $ownershipId);
        }

        $contracts = $query->with(['invoices', 'units'])->get();

        $logger->debug('Queried contracts for invoicing', [
            'ownership_id' => $ownershipId,
            'today' => $today->toDateString(),
            'contracts_count' => $contracts->count(),
        ]);

        return $contracts;
    }

    /**
     * Calculate the next billing period for a contract based on its payment frequency.
     *
     * This method now uses ContractInvoiceService to ensure consistency.
     *
     * @return array{start:Carbon,end:Carbon,due:Carbon}|null
     */
    protected function calculateBillingPeriod(Contract $contract): ?array
    {
        return $this->contractInvoiceService->calculateNextPeriod($contract);
    }

    /**
     * Send invoice notifications (email, SMS, system notification) when invoice is sent.
     *
     * @param Invoice $invoice
     * @param \Illuminate\Log\Logger $logger
     * @return void
     */
    protected function sendInvoiceNotifications(Invoice $invoice, $logger): void
    {
        $ownershipId = $invoice->ownership_id;
        $contract = $invoice->contract;
        
        if (!$contract) {
            $logger->warning('Cannot send invoice notifications - contract not found', [
                'invoice_id' => $invoice->id,
            ]);
            return;
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


