<?php

namespace Database\Seeders\V1\Payment;

use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Payment\Payment;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
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

        $this->command->info('Creating payments for ' . $ownerships->count() . ' ownerships...');
        $this->command->info('');

        foreach ($ownerships as $ownership) {
            $this->command->info("Processing ownership: {$ownership->name}");

            // Get paid or sent invoices for this ownership
            $invoices = Invoice::where('ownership_id', $ownership->id)
                ->whereIn('status', ['paid', 'sent', 'overdue'])
                ->get();

            if ($invoices->isEmpty()) {
                $this->command->warn("  No invoices found for ownership: {$ownership->name}");
                continue;
            }

            foreach ($invoices as $invoice) {
                // Create 0-2 payments per invoice (some invoices may have partial payments)
                $paymentsCount = rand(0, 2);
                
                if ($paymentsCount === 0) {
                    continue; // Some invoices have no payments yet
                }

                $totalPaid = 0;
                $remainingAmount = $invoice->total;

                for ($i = 0; $i < $paymentsCount; $i++) {
                    // Last payment covers remaining amount, others are partial
                    if ($i === $paymentsCount - 1) {
                        $amount = $remainingAmount;
                    } else {
                        $amount = min($remainingAmount * 0.5, $remainingAmount - 100); // 50% or remaining - 100
                    }

                    $status = $this->getRandomPaymentStatus();
                    $paidAt = $status === 'paid' ? $this->getPaidAt($invoice->due) : null;

                    $payment = Payment::create([
                        'uuid' => (string) Str::uuid(),
                        'invoice_id' => $invoice->id,
                        'ownership_id' => $ownership->id,
                        'method' => $this->getRandomPaymentMethod(),
                        'transaction_id' => $this->generateTransactionId(),
                        'amount' => $amount,
                        'currency' => 'SAR',
                        'status' => $status,
                        'paid_at' => $paidAt,
                        'confirmed_by' => null,
                    ]);

                    $totalPaid += $amount;
                    $remainingAmount -= $amount;

                    $this->command->info("    ✓ Created payment: {$payment->method} - {$payment->amount} SAR - {$payment->status}");

                    // If payment is paid and covers full amount, update invoice status
                    if ($status === 'paid' && $totalPaid >= $invoice->total) {
                        $invoice->update([
                            'status' => 'paid',
                            'paid_at' => $paidAt,
                        ]);
                    }
                }
            }

            $this->command->info("  ✓ Completed payments for {$ownership->name}");
            $this->command->info('');
        }

        $this->command->info('✅ Payments seeded successfully!');
    }

    /**
     * Get random payment status
     */
    private function getRandomPaymentStatus(): string
    {
        $statuses = ['pending', 'paid', 'unpaid'];
        $weights = ['paid' => 6, 'pending' => 2, 'unpaid' => 1];
        return $this->getWeightedRandom($weights);
    }

    /**
     * Get random payment method
     */
    private function getRandomPaymentMethod(): string
    {
        $methods = ['cash', 'bank_transfer', 'check', 'other'];
        $weights = ['bank_transfer' => 5, 'cash' => 2, 'check' => 2, 'other' => 1];
        return $this->getWeightedRandom($weights);
    }

    /**
     * Generate transaction ID
     */
    private function generateTransactionId(): ?string
    {
        // 80% chance of having transaction ID
        if (rand(1, 10) <= 8) {
            return 'TXN' . date('Ymd') . rand(100000, 999999);
        }
        
        return null;
    }

    /**
     * Get paid_at timestamp
     */
    private function getPaidAt($dueDate): ?string
    {
        // Paid date is between due date and now (if past) or around due date (if future)
        if ($dueDate->isPast()) {
            return $dueDate->copy()->addDays(rand(-5, 30))->format('Y-m-d H:i:s');
        } else {
            return $dueDate->copy()->subDays(rand(0, 5))->format('Y-m-d H:i:s');
        }
    }

    /**
     * Get weighted random value
     */
    private function getWeightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        
        $current = 0;
        foreach ($weights as $key => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $key;
            }
        }
        
        return array_key_first($weights);
    }
}

