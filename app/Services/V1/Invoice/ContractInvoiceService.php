<?php

namespace App\Services\V1\Invoice;

use App\Models\V1\Contract\Contract;
use App\Models\V1\Invoice\Invoice;
use App\Repositories\V1\Invoice\Interfaces\InvoiceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContractInvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    /**
     * Generate invoice from contract
     *
     * @param Contract $contract
     * @param array{start:string,end:string,due:string,number?:string,status?:string,generated_by?:int,generated_at?:string} $period
     * @return Invoice
     */
    public function generateFromContract(Contract $contract, array $period): Invoice
    {
        return DB::transaction(function () use ($contract, $period) {
            // Validate period
            $this->validatePeriod($contract, [
                'start' => $period['start'],
                'end' => $period['end'],
            ]);

            // Calculate amount from contract based on period length
            $amount = $this->calculateAmountFromContract($contract, [
                'start' => $period['start'],
                'end' => $period['end'],
            ]);

            // Generate invoice number if not provided
            $invoiceNumber = $period['number'] ?? $this->generateInvoiceNumber($contract->ownership_id);

            // Validate and normalize status
            $status = $period['status'] ?? 'draft';
            $statusEnum = \App\Enums\V1\Invoice\InvoiceStatus::tryFrom($status);
            
            if (!$statusEnum) {
                Log::warning('Invalid invoice status provided, falling back to draft', [
                    'contract_id' => $contract->id,
                    'invalid_status' => $status,
                ]);
                $status = 'draft';
                $statusEnum = \App\Enums\V1\Invoice\InvoiceStatus::DRAFT;
            }
            
            // For system-generated invoices, only allow draft, pending, or sent as initial status
            // Other statuses (paid, partial, overdue, etc.) should be set through transitions
            $allowedInitialStatuses = [
                \App\Enums\V1\Invoice\InvoiceStatus::DRAFT,
                \App\Enums\V1\Invoice\InvoiceStatus::PENDING,
                \App\Enums\V1\Invoice\InvoiceStatus::SENT,
            ];
            
            if (!in_array($statusEnum, $allowedInitialStatuses)) {
                Log::warning('Invoice status is not allowed for initial creation, falling back to draft', [
                    'contract_id' => $contract->id,
                    'invalid_status' => $status,
                    'allowed_statuses' => array_map(fn($s) => $s->value, $allowedInitialStatuses),
                ]);
                $status = 'draft';
            }

            // Create invoice using repository directly
            return $this->invoiceRepository->create([
                'uuid' => Str::uuid(),
                'contract_id' => $contract->id,
                'ownership_id' => $contract->ownership_id,
                'number' => $invoiceNumber,
                'period_start' => $period['start'],
                'period_end' => $period['end'],
                'due' => $period['due'],
                'amount' => $amount,
                'tax_from_contract' => true,
                'tax' => null,
                'tax_rate' => null,
                'total' => $amount,
                'status' => $status,
                'generated_by' => $period['generated_by'] ?? null,
                'generated_at' => $period['generated_at'] ?? now(),
            ]);
        });
    }

    /**
     * Calculate next period for contract
     *
     * Rules:
     * 1. Start from last invoice period_end + 1 day
     * 2. Length based on payment_frequency
     * 3. Respect manual invoices (start from their end)
     * 4. Cap by contract end date
     *
     * @param Contract $contract
     * @return array{start:Carbon,end:Carbon,due:Carbon}|null
     */
    public function calculateNextPeriod(Contract $contract): ?array
    {
        $lastInvoice = $this->getLastInvoice($contract);

        if ($lastInvoice) {
            $startDate = Carbon::parse($lastInvoice->period_end)->addDay();
        } else {
            $startDate = Carbon::parse($contract->start);
        }

        // Check if contract expired
        $contractEnd = Carbon::parse($contract->end);
        if ($startDate->gt($contractEnd)) {
            return null;
        }

        // Calculate period length based on frequency
        $monthsToAdd = $this->getMonthsForFrequency($contract->payment_frequency);
        $endDate = $startDate->copy()->addMonths($monthsToAdd)->subDay();

        // Cap by contract end
        if ($endDate->gt($contractEnd)) {
            $endDate = $contractEnd->copy();
        }

        // Calculate due date using invoice_due_days_after_period_start from settings
        // Due date is calculated from period START, not period END (for advance payment)
        // Example: Period 1-1 to 31-3, due_days_after_period_start = 10, then due = 11-1
        $invoiceSettingService = app(\App\Services\V1\Invoice\InvoiceSettingService::class);
        $dueDaysAfterPeriodStart = $invoiceSettingService->getDueDaysAfterPeriodStart($contract->ownership_id);
        $dueDate = $startDate->copy()->addDays($dueDaysAfterPeriodStart);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'due' => $dueDate,
        ];
    }

    /**
     * Validate period for contract
     *
     * Checks:
     * 1. Period is within contract dates
     * 2. No overlapping with existing invoices (except excluded invoice)
     * 3. Period dates are valid (start <= end)
     *
     * @param Contract $contract
     * @param array{start:string,end:string} $period
     * @param Invoice|null $excludeInvoice Invoice to exclude from overlap check (for updates)
     * @return void
     * @throws \Exception
     */
    public function validatePeriod(Contract $contract, array $period, ?Invoice $excludeInvoice = null): void
    {
        $start = Carbon::parse($period['start']);
        $end = Carbon::parse($period['end']);
        $contractStart = Carbon::parse($contract->start);
        $contractEnd = Carbon::parse($contract->end);

        // 1. Check if period dates are valid
        if ($start->gt($end)) {
            throw new \Exception('Invoice period start date must be before or equal to end date');
        }

        // 2. Check if period is within contract dates
        if ($start->lt($contractStart) || $end->gt($contractEnd)) {
            throw new \Exception('Invoice period must be within contract dates');
        }

        // 3. Check for overlapping invoices (excluding the current invoice if updating)
        $query = Invoice::where('contract_id', $contract->id);
        
        if ($excludeInvoice) {
            $query->where('id', '!=', $excludeInvoice->id);
        }
        
        $overlapping = $query->where(function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start, $end) {
                    // Overlap: start or end is within existing period
                    $q->whereBetween('period_start', [$start->toDateString(), $end->toDateString()])
                        ->orWhereBetween('period_end', [$start->toDateString(), $end->toDateString()]);
                })
                ->orWhere(function ($q) use ($start, $end) {
                    // Overlap: existing period contains new period
                    $q->where('period_start', '<=', $start->toDateString())
                        ->where('period_end', '>=', $end->toDateString());
                })
                ->orWhere(function ($q) use ($start, $end) {
                    // Overlap: new period contains existing period
                    $q->where('period_start', '>=', $start->toDateString())
                        ->where('period_end', '<=', $end->toDateString());
                });
            })
            ->exists();

        if ($overlapping) {
            throw new \Exception('Invoice period overlaps with existing invoice');
        }
    }

    /**
     * Get last invoice for contract (manual or auto)
     *
     * @param Contract $contract
     * @return Invoice|null
     */
    private function getLastInvoice(Contract $contract): ?Invoice
    {
        return Invoice::where('contract_id', $contract->id)
            ->orderBy('period_end', 'desc')
            ->first();
    }

    /**
     * Calculate amount from contract based on period length
     *
     * For regular payment frequencies (monthly, quarterly, etc.), use fixed amounts.
     * For custom periods, calculate based on actual days.
     *
     * @param Contract $contract
     * @param array{start:string,end:string} $period
     * @return float
     */
    public function calculateAmountFromContract(Contract $contract, array $period): float
    {
        $logger = Log::channel('invoices');
        $totalRent = (float) $contract->total_rent;
        $start = Carbon::parse($period['start']);
        $end = Carbon::parse($period['end']);
        $paymentFrequency = $contract->payment_frequency ?? 'monthly';

        // For regular payment frequencies, use fixed amounts per period
        // This ensures consistent amounts regardless of month length
        $calculatedAmount = match ($paymentFrequency) {
            'monthly' => round($totalRent / 12, 2),
            'quarterly' => round($totalRent / 4, 2),
            'semi_annually' => round($totalRent / 2, 2),
            'yearly' => round($totalRent, 2),
            'weekly' => round($totalRent / 52, 2),
            default => $this->calculateAmountByDays($totalRent, $start, $end, $logger),
        };

        // Detailed logging for debugging
        $logger->debug('Invoice amount calculation details', [
            'contract_id' => $contract->id,
            'contract_number' => $contract->number ?? 'N/A',
            'payment_frequency' => $paymentFrequency,
            'total_rent' => $totalRent,
            'base_rent' => $contract->base_rent ?? 'N/A',
            'rent_fees' => $contract->rent_fees ?? 'N/A',
            'vat_amount' => $contract->vat_amount ?? 'N/A',
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'calculation_method' => in_array($paymentFrequency, ['monthly', 'quarterly', 'semi_annually', 'yearly', 'weekly']) 
                ? 'fixed_by_frequency' 
                : 'calculated_by_days',
            'calculated_amount' => $calculatedAmount,
            'expected_monthly' => round($totalRent / 12, 2),
        ]);

        return $calculatedAmount;
    }

    /**
     * Calculate amount based on actual days (for custom periods)
     *
     * @param float $totalRent
     * @param Carbon $start
     * @param Carbon $end
     * @param \Illuminate\Log\Logger $logger
     * @return float
     */
    private function calculateAmountByDays(float $totalRent, Carbon $start, Carbon $end, $logger): float
    {
        // Calculate period length in months (with decimals for accuracy)
        // Use diffInMonths for whole months
        $wholeMonths = $start->diffInMonths($end);
        
        // Calculate remaining days after whole months
        $dateAfterMonths = $start->copy()->addMonths($wholeMonths);
        $remainingDays = $dateAfterMonths->diffInDays($end);
        
        // Convert remaining days to fraction of month (using 30 days as average)
        $months = $wholeMonths + ($remainingDays / 30.0);

        // Calculate amount: (total_rent / 12) * months
        $calculatedAmount = round(($totalRent / 12) * $months, 2);

        // Log details for custom period calculation
        $logger->debug('Custom period calculation by days', [
            'wholeMonths' => $wholeMonths,
            'dateAfterMonths' => $dateAfterMonths->toDateString(),
            'remainingDays' => $remainingDays,
            'months_calculated' => $months,
            'calculation_formula' => "({$totalRent} / 12) * {$months}",
            'calculated_amount' => $calculatedAmount,
        ]);

        return $calculatedAmount;
    }

    /**
     * Get months for frequency
     *
     * @param string $frequency
     * @return int
     */
    private function getMonthsForFrequency(string $frequency): int
    {
        return match ($frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'yearly' => 12,
            default => 1,
        };
    }

    /**
     * Check if period overlaps with existing invoices
     *
     * @param Contract $contract
     * @param array{start:string,end:string} $period
     * @return bool
     */
    public function hasOverlappingInvoice(Contract $contract, array $period): bool
    {
        try {
            $this->validatePeriod($contract, $period);
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Generate unique invoice number
     *
     * Format: INV-{ownership_id}-{year}-{sequence}
     * Example: INV-001-2025-00001
     *
     * @param int $ownershipId
     * @return string
     */
    private function generateInvoiceNumber(int $ownershipId): string
    {
        $year = date('Y');
        $prefix = 'INV-' . str_pad($ownershipId, 3, '0', STR_PAD_LEFT) . '-' . $year . '-';
        
        // Get the last invoice number for this ownership and year
        $lastInvoice = Invoice::where('ownership_id', $ownershipId)
            ->where('number', 'like', $prefix . '%')
            ->orderBy('number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            // Extract sequence number from last invoice
            $lastNumber = $lastInvoice->number;
            $lastSequence = (int) substr($lastNumber, strrpos($lastNumber, '-') + 1);
            $sequence = $lastSequence + 1;
        } else {
            // First invoice for this ownership and year
            $sequence = 1;
        }
        
        return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}

