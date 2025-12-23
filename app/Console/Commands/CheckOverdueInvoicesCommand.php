<?php

namespace App\Console\Commands;

use App\Enums\V1\Invoice\InvoiceStatus;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Ownership\Ownership;
use App\Services\V1\Invoice\InvoiceSettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOverdueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and mark overdue invoices based on due date and settings.';

    public function __construct(
        private InvoiceSettingService $invoiceSettings
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting overdue invoices check...');

        $ownerships = Ownership::all();
        $totalChecked = 0;
        $totalMarked = 0;
        $totalErrors = 0;

        foreach ($ownerships as $ownership) {
            $this->info("Processing ownership: {$ownership->name} (ID: {$ownership->id})");

            try {
                // Get all invoices that are not paid, cancelled, or refunded
                $invoices = Invoice::where('ownership_id', $ownership->id)
                    ->whereNotIn('status', InvoiceStatus::SENT->value,)
                    ->where('due', '<', now())
                    ->get();

                $checked = $invoices->count();
                $totalChecked += $checked;

                foreach ($invoices as $invoice) {
                    try {
                        // Check if invoice is actually overdue (due date passed and not paid)
                        if (
                            $invoice->due->isPast() &&
                            !in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED, InvoiceStatus::REFUNDED])
                        ) {

                            // Get overdue handling method from settings
                            $overdueHandling = $this->invoiceSettings->getOverdueHandling($ownership->id);

                            // Only mark as overdue if not already overdue
                            if ($invoice->status !== InvoiceStatus::OVERDUE) {
                                $invoice->transitionTo(
                                    InvoiceStatus::OVERDUE,
                                    'Due date passed - automatically marked as overdue',
                                    null // System action
                                );
                                $totalMarked++;
                                $this->line("  ✓ Marked invoice #{$invoice->number} as overdue");
                            }

                            // Apply overdue handling actions based on settings
                            $this->applyOverdueHandling($invoice, $overdueHandling, $ownership->id);
                        }
                    } catch (\Throwable $e) {
                        $totalErrors++;
                        Log::error("Failed to mark invoice {$invoice->id} as overdue: {$e->getMessage()}", [
                            'invoice_id' => $invoice->id,
                            'ownership_id' => $ownership->id,
                            'exception' => $e,
                        ]);
                        $this->warn("  ✗ Error marking invoice #{$invoice->number}: {$e->getMessage()}");
                    }
                }

                if ($checked > 0) {
                    $this->info("  Checked {$checked} invoices, marked {$totalMarked} as overdue.");
                }
            } catch (\Throwable $e) {
                $totalErrors++;
                Log::error("Failed to process ownership {$ownership->id} for overdue check: {$e->getMessage()}", [
                    'ownership_id' => $ownership->id,
                    'exception' => $e,
                ]);
                $this->warn("  ✗ Error processing ownership {$ownership->name}: {$e->getMessage()}");
            }
        }

        $this->info("Overdue check completed. Total checked: {$totalChecked}, Total marked: {$totalMarked}, Errors: {$totalErrors}.");

        return Command::SUCCESS;
    }

    /**
     * Apply overdue handling actions based on settings.
     * 
     * @param Invoice $invoice
     * @param string $handling 'notify'|'penalty'|'block'
     * @param int $ownershipId
     */
    protected function applyOverdueHandling(Invoice $invoice, string $handling, int $ownershipId): void
    {
        switch ($handling) {
            case 'penalty':
                // Apply penalty rate
                $penaltyRate = $this->invoiceSettings->getOverduePenaltyRate($ownershipId);
                if ($penaltyRate > 0) {
                    $penaltyAmount = $invoice->total * ($penaltyRate / 100);
                    // Create penalty invoice item or add to existing invoice
                    // For now, log the penalty calculation
                    Log::info('Overdue penalty calculated', [
                        'invoice_id' => $invoice->id,
                        'penalty_rate' => $penaltyRate,
                        'penalty_amount' => $penaltyAmount,
                        'invoice_total' => $invoice->total,
                    ]);
                    $this->line("  ⚠ Penalty calculated for invoice #{$invoice->number}: {$penaltyAmount} ({$penaltyRate}%)");
                }
                break;

            case 'block':
                // Block tenant actions (e.g., prevent new contracts, access to portal)
                // Implement tenant blocking logic
                Log::info('Overdue invoice - tenant should be blocked', [
                    'invoice_id' => $invoice->id,
                    'contract_id' => $invoice->contract_id,
                ]);
                $this->line("  🚫 Tenant should be blocked for invoice #{$invoice->number}");
                break;

            case 'notify':
            default:
                // Notify only (already handled by marking as OVERDUE)
                // Additional notifications can be sent here if needed
                break;
        }
    }
}
