<?php

namespace App\Services\V1\Invoice;

use App\Models\V1\Contract\Contract;
use App\Models\V1\Invoice\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutomatedInvoiceService
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    /**
     * Generate draft invoices for all contracts that are due for invoicing today.
     *
     * Returns the number of invoices successfully generated.
     */
    public function generateInvoicesForDueContracts(): int
    {
        $contracts = $this->getContractsDueForInvoicing();
        $generated = 0;

        foreach ($contracts as $contract) {
            try {
                $invoice = $this->generateInvoiceForContract($contract);

                if ($invoice) {
                    $generated++;
                }
            } catch (\Throwable $e) {
                Log::error("Failed to auto-generate invoice for contract {$contract->id}: {$e->getMessage()}", [
                    'contract_id' => $contract->id,
                    'exception' => $e,
                ]);
            }
        }

        return $generated;
    }

    /**
     * Generate a single draft invoice for a specific contract if it is due.
     *
     * Returns the created invoice, or null if no invoice should be generated (already invoiced / out of range).
     */
    public function generateInvoiceForContract(Contract $contract): ?Invoice
    {
        // Ensure contract is active and within date range
        if ($contract->status !== 'active') {
            return null;
        }

        $period = $this->calculateBillingPeriod($contract);

        if ($period === null) {
            // No more billable periods (contract ended or invalid dates)
            return null;
        }

        $today = Carbon::today();

        // Only generate when the current date is within or after the billing period start
        if ($period['start']->isFuture()) {
            return null;
        }

        // Prevent duplicate invoices for the same period
        $exists = $contract->invoices()
            ->whereDate('period_start', $period['start']->toDateString())
            ->whereDate('period_end', $period['end']->toDateString())
            ->exists();

        if ($exists) {
            return null;
        }

        // Build payload for InvoiceService::generateFromContract
        $payload = [
            'start' => $period['start']->toDateString(),
            'end' => $period['end']->toDateString(),
            'due' => $period['due']->toDateString(),
            'status' => 'draft',           // System-generated draft
            'generated_by' => null,        // System
            'generated_at' => now(),
        ];

        return DB::transaction(function () use ($contract, $payload) {
            return $this->invoiceService->generateFromContract($contract, $payload);
        });
    }

    /**
     * Get all active contracts that are candidates for invoicing.
     *
     * We keep the query simple and rely on calculateBillingPeriod() to decide if a new invoice is needed.
     */
    protected function getContractsDueForInvoicing(): Collection
    {
        $today = Carbon::today();

        return Contract::query()
            ->where('status', 'active')
            ->whereDate('start', '<=', $today)
            ->whereDate('end', '>=', $today)
            ->with(['invoices', 'units'])
            ->get();
    }

    /**
     * Calculate the next billing period for a contract based on its payment frequency.
     *
     * Logic:
     * - If there is a previous invoice, next period starts the day after last period_end.
     * - Otherwise, it starts at contract->start.
     * - Period length is based on payment_frequency (monthly/quarterly/yearly/weekly).
     * - Period end is capped by contract->end.
     * - Returns null if start > contract->end (no more billable periods).
     *
     * @return array{start:Carbon,end:Carbon,due:Carbon}|null
     */
    protected function calculateBillingPeriod(Contract $contract): ?array
    {
        /** @var Invoice|null $lastInvoice */
        $lastInvoice = $contract->invoices()->orderByDesc('period_end')->first();

        if ($lastInvoice) {
            $startDate = $lastInvoice->period_end->copy()->addDay();
        } else {
            $startDate = $contract->start->copy();
        }

        // If start date is after contract end, nothing to bill
        if ($startDate->gt($contract->end)) {
            return null;
        }

        // Determine period end based on payment frequency
        switch ($contract->payment_frequency) {
            case 'weekly':
                $endDate = $startDate->copy()->addWeek()->subDay();
                break;
            case 'quarterly':
                $endDate = $startDate->copy()->addMonths(3)->subDay();
                break;
            case 'yearly':
                $endDate = $startDate->copy()->addYear()->subDay();
                break;
            case 'monthly':
            default:
                $endDate = $startDate->copy()->addMonth()->subDay();
                break;
        }

        // Cap by contract end date
        if ($endDate->gt($contract->end)) {
            $endDate = $contract->end->copy();
        }

        // Due date: end of period + 7 days (can be customized later)
        $dueDate = $endDate->copy()->addDays(7);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'due' => $dueDate,
        ];
    }
}


