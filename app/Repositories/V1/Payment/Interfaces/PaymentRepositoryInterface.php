<?php

namespace App\Repositories\V1\Payment\Interfaces;

use App\Models\V1\Payment\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    /**
     * Get all payments with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all payments.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find payment by ID.
     */
    public function find(int $id): ?Payment;

    /**
     * Find payment by UUID.
     */
    public function findByUuid(string $uuid): ?Payment;

    /**
     * Get payments for an invoice.
     */
    public function getByInvoice(int $invoiceId): Collection;

    /**
     * Create a new payment.
     */
    public function create(array $data): Payment;

    /**
     * Update payment.
     */
    public function update(Payment $payment, array $data): Payment;

    /**
     * Delete payment.
     */
    public function delete(Payment $payment): bool;

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(Payment $payment, int $confirmedBy): Payment;

    /**
     * Mark payment as unpaid.
     */
    public function markAsUnpaid(Payment $payment): Payment;
}

