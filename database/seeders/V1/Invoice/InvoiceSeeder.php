<?php

namespace Database\Seeders\V1\Invoice;

use App\Models\V1\Contract\Contract;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Invoice\InvoiceItem;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ownershipRepository = app(OwnershipRepositoryInterface::class);
        $ownerships = $ownershipRepository->all();

        if ($ownerships->isEmpty()) {
            $this->command->warn('No ownerships found. Please run OwnershipSeeder first.');
            return;
        }

        $this->command->info('Creating invoices for ' . $ownerships->count() . ' ownerships...');
        $this->command->info('');

        foreach ($ownerships as $ownership) {
            $this->command->info("Processing ownership: {$ownership->name}");

            // Get active contracts for this ownership
            $contracts = Contract::where('ownership_id', $ownership->id)
                ->where('status', 'active')
                ->get();

            if ($contracts->isEmpty()) {
                $this->command->warn("  No active contracts found for ownership: {$ownership->name}");
                continue;
            }

            // Counter for unique invoice numbers per ownership
            $invoiceCounter = 1;

            foreach ($contracts as $contract) {
                // Create 1-6 invoices per contract (representing monthly invoices)
                $invoicesCount = rand(1, 6);
                
                for ($i = 0; $i < $invoicesCount; $i++) {
                    $periodStart = now()->subMonths($invoicesCount - $i - 1)->startOfMonth();
                    $periodEnd = $periodStart->copy()->endOfMonth();
                    $dueDate = $periodEnd->copy()->addDays(rand(5, 15));
                    
                    $amount = $contract->rent;
                    $taxRate = 15.00; // Saudi VAT
                    $tax = $amount * ($taxRate / 100);
                    $total = $amount + $tax;

                    $invoice = Invoice::create([
                        'uuid' => (string) Str::uuid(),
                        'contract_id' => $contract->id,
                        'ownership_id' => $ownership->id,
                        'number' => $this->generateInvoiceNumber($ownership->id, $invoiceCounter++),
                        'period_start' => $periodStart->format('Y-m-d'),
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'due' => $dueDate->format('Y-m-d'),
                        'amount' => $amount,
                        'tax' => $tax,
                        'tax_rate' => $taxRate,
                        'total' => $total,
                        'status' => $this->getRandomInvoiceStatus($dueDate),
                        'notes' => $this->getRandomNotes(),
                        'generated_by' => null,
                        'generated_at' => $periodStart->format('Y-m-d H:i:s'),
                        'paid_at' => $this->getPaidAt($dueDate),
                    ]);

                    // Create invoice items
                    $this->createInvoiceItems($invoice, $contract);

                    $this->command->info("    ✓ Created invoice: {$invoice->number} - {$invoice->status}");
                }
            }

            $this->command->info("  ✓ Completed invoices for {$ownership->name}");
            $this->command->info('');
        }

        $this->command->info('✅ Invoices seeded successfully!');
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber(int $ownershipId, int $index): string
    {
        return 'INV-' . str_pad($ownershipId, 3, '0', STR_PAD_LEFT) . '-' . date('Y') . '-' . str_pad($index, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get random invoice status based on due date
     */
    private function getRandomInvoiceStatus($dueDate): string
    {
        $now = now();
        
        if ($dueDate->isPast()) {
            // Past due date - could be paid or overdue
            $statuses = ['paid', 'overdue', 'paid', 'paid']; // More likely to be paid
            return $statuses[array_rand($statuses)];
        } else {
            // Future due date - draft or sent
            $statuses = ['sent', 'draft', 'sent'];
            return $statuses[array_rand($statuses)];
        }
    }

    /**
     * Get paid_at timestamp if invoice is paid
     */
    private function getPaidAt($dueDate): ?string
    {
        if (rand(0, 1)) { // 50% chance of being paid
            // Paid date is between due date and now (if past) or between period_end and due_date (if future)
            if ($dueDate->isPast()) {
                return $dueDate->copy()->addDays(rand(0, 30))->format('Y-m-d H:i:s');
            } else {
                return null; // Future invoices not paid yet
            }
        }
        
        return null;
    }

    /**
     * Get random notes
     */
    private function getRandomNotes(): ?string
    {
        $notes = [
            'Monthly rent invoice',
            'Rent payment for the period',
            null,
            null,
        ];
        
        return $notes[array_rand($notes)];
    }

    /**
     * Create invoice items
     */
    private function createInvoiceItems(Invoice $invoice, Contract $contract): void
    {
        $contract->loadMissing('units');

        if ($contract->units->isEmpty()) {
            // Main rent item (single-unit fallback)
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'rent',
                'description' => 'Monthly rent for ' . $invoice->period_start . ' to ' . $invoice->period_end,
                'quantity' => 1,
                'unit_price' => $contract->rent,
                'total' => $contract->rent,
            ]);
        } else {
            // One item per unit
            foreach ($contract->units as $unit) {
                $pivotRent = $unit->pivot?->rent_amount;
                $unitRent = $pivotRent !== null ? (float) $pivotRent : (float) $contract->rent;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'type' => 'rent',
                    'description' => 'Monthly rent for unit ' . ($unit->number ?? $unit->id) . ' for ' . $invoice->period_start . ' to ' . $invoice->period_end,
                    'quantity' => 1,
                    'unit_price' => $unitRent,
                    'total' => $unitRent,
                ]);
            }
        }

        // Sometimes add additional items (service fees, etc.)
        if (rand(0, 1)) { // 50% chance
            $serviceFee = rand(100, 500);
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'service_fee',
                'description' => 'Building maintenance fee',
                'quantity' => 1,
                'unit_price' => $serviceFee,
                'total' => $serviceFee,
            ]);
        }
    }
}

