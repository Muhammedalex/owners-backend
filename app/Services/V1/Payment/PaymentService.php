<?php

namespace App\Services\V1\Payment;

use App\Models\V1\Payment\Payment;
use App\Repositories\V1\Payment\Interfaces\PaymentRepositoryInterface;
use App\Services\V1\Document\DocumentService;
use App\Services\V1\Media\MediaService;
use App\Services\V1\Invoice\InvoiceSettingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private MediaService $mediaService,
        private DocumentService $documentService,
        private InvoiceSettingService $invoiceSettings
    ) {}

    /**
     * Get all payments with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->paymentRepository->paginate($perPage, $filters);
    }

    /**
     * Get all payments.
     */
    public function all(array $filters = []): Collection
    {
        return $this->paymentRepository->all($filters);
    }

    /**
     * Find payment by ID.
     */
    public function find(int $id): ?Payment
    {
        return $this->paymentRepository->find($id);
    }

    /**
     * Find payment by UUID.
     */
    public function findByUuid(string $uuid): ?Payment
    {
        return $this->paymentRepository->findByUuid($uuid);
    }

    /**
     * Get payments for an invoice.
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return $this->paymentRepository->getByInvoice($invoiceId);
    }

    /**
     * Create a new payment (status recording only).
     * 
     * If status is 'paid' or not provided (defaults to 'paid' for collectors),
     * the payment will be automatically confirmed and invoice status will be updated.
     */
    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            // Validate partial payment if invoice exists
            if (isset($data['invoice_id']) && isset($data['amount'])) {
                $invoice = \App\Models\V1\Invoice\Invoice::find($data['invoice_id']);
                if ($invoice) {
                    $ownershipId = $invoice->ownership_id;
                    $allowPartialPayment = $this->invoiceSettings->allowPartialPayment($ownershipId);
                    
                    // Calculate total paid including this new payment
                    $existingPaid = $invoice->payments()->where('status', 'paid')->sum('amount');
                    $newTotalPaid = $existingPaid + $data['amount'];
                    
                    // Check if this would be a partial payment
                    if ($newTotalPaid < $invoice->total && $newTotalPaid > 0) {
                        if (!$allowPartialPayment) {
                            throw new \Exception('Partial payments are not allowed for this ownership. Payment amount must equal the invoice total.');
                        }
                    }
                    
                    // Check if payment exceeds invoice total
                    if ($newTotalPaid > $invoice->total) {
                        throw new \Exception('Payment amount exceeds invoice total. Maximum allowed: ' . ($invoice->total - $existingPaid));
                    }
                }
            }
            
            // If status is not provided, default to 'paid' (collector enters payment directly)
            if (!isset($data['status'])) {
                $data['status'] = 'paid';
                $data['paid_at'] = $data['paid_at'] ?? now();
            }
            
            $payment = $this->paymentRepository->create($data);
            
            // Auto-update invoice status based on payments
            // This will check all payments with status='paid' and update invoice accordingly
            if ($payment->invoice) {
                $payment->invoice->updateStatusFromPayments();
            }
            
            return $payment;
        });
    }

    /**
     * Update payment.
     */
    public function update(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            // Validate partial payment if amount is being updated
            if (isset($data['amount']) && $payment->invoice) {
                $invoice = $payment->invoice;
                $ownershipId = $invoice->ownership_id;
                $allowPartialPayment = $this->invoiceSettings->allowPartialPayment($ownershipId);
                
                // Calculate total paid with updated amount
                $existingPaid = $invoice->payments()
                    ->where('status', 'paid')
                    ->where('id', '!=', $payment->id)
                    ->sum('amount');
                $newTotalPaid = $existingPaid + $data['amount'];
                
                // Check if this would be a partial payment
                if ($newTotalPaid < $invoice->total && $newTotalPaid > 0) {
                    if (!$allowPartialPayment) {
                        throw new \Exception('Partial payments are not allowed for this ownership. Payment amount must equal the invoice total.');
                    }
                }
                
                // Check if payment exceeds invoice total
                if ($newTotalPaid > $invoice->total) {
                    throw new \Exception('Payment amount exceeds invoice total. Maximum allowed: ' . ($invoice->total - $existingPaid));
                }
            }
            
            $updatedPayment = $this->paymentRepository->update($payment, $data);
            
            // Auto-update invoice status if payment status or amount changed
            if ((isset($data['status']) || isset($data['amount'])) && $updatedPayment->invoice) {
                $updatedPayment->invoice->updateStatusFromPayments();
            }
            
            return $updatedPayment;
        });
    }

    /**
     * Delete payment.
     */
    public function delete(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            // Store invoice reference before deletion
            $invoice = $payment->invoice;
            
            // Load relationships
            $payment->load(['mediaFiles', 'documents']);

            // Delete all media files
            foreach ($payment->mediaFiles as $mediaFile) {
                $this->mediaService->delete($mediaFile);
            }

            // Delete all documents
            foreach ($payment->documents as $document) {
                $this->documentService->delete($document);
            }

            $deleted = $this->paymentRepository->delete($payment);
            
            // Auto-update invoice status after payment deletion
            if ($invoice) {
                $invoice->updateStatusFromPayments();
            }
            
            return $deleted;
        });
    }

    /**
     * Mark payment as paid (manual confirmation).
     */
    public function markAsPaid(Payment $payment, int $confirmedBy): Payment
    {
        return DB::transaction(function () use ($payment, $confirmedBy) {
            $payment = $this->paymentRepository->markAsPaid($payment, $confirmedBy);

            // Auto-update invoice status based on payments (uses transitionTo and proper status enum)
            if ($payment->invoice) {
                $payment->invoice->updateStatusFromPayments();
            }

            return $payment;
        });
    }

    /**
     * Mark payment as unpaid.
     */
    public function markAsUnpaid(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment = $this->paymentRepository->markAsUnpaid($payment);

            // Auto-update invoice status based on payments (uses transitionTo and proper status enum)
            if ($payment->invoice) {
                $payment->invoice->updateStatusFromPayments();
            }

            return $payment;
        });
    }
}

