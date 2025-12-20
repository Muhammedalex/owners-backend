<?php

namespace App\Services\V1\Payment;

use App\Models\V1\Payment\Payment;
use App\Repositories\V1\Payment\Interfaces\PaymentRepositoryInterface;
use App\Services\V1\Document\DocumentService;
use App\Services\V1\Media\MediaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private MediaService $mediaService,
        private DocumentService $documentService
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
     */
    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            return $this->paymentRepository->create($data);
        });
    }

    /**
     * Update payment.
     */
    public function update(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            return $this->paymentRepository->update($payment, $data);
        });
    }

    /**
     * Delete payment.
     */
    public function delete(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
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

            return $this->paymentRepository->delete($payment);
        });
    }

    /**
     * Mark payment as paid (manual confirmation).
     */
    public function markAsPaid(Payment $payment, int $confirmedBy): Payment
    {
        return DB::transaction(function () use ($payment, $confirmedBy) {
            $payment = $this->paymentRepository->markAsPaid($payment, $confirmedBy);

            // Update invoice status if all payments cover the invoice total
            $invoice = $payment->invoice;
            $totalPaid = $invoice->payments()->where('status', 'paid')->sum('amount');
            
            if ($totalPaid >= $invoice->total) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
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

            // Update invoice status if needed
            $invoice = $payment->invoice;
            $totalPaid = $invoice->payments()->where('status', 'paid')->sum('amount');
            
            if ($totalPaid < $invoice->total && $invoice->status === 'paid') {
                $invoice->update([
                    'status' => 'sent',
                    'paid_at' => null,
                ]);
            }

            return $payment;
        });
    }
}

