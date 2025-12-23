<?php

namespace App\Enums\V1\Invoice;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case SENT = 'sent';
    case VIEWED = 'viewed';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    
    /**
     * Get all statuses.
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Get status label (Arabic).
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::PENDING => 'قيد الانتظار',
            self::SENT => 'تم الإرسال',
            self::VIEWED => 'تم المشاهدة',
            self::PARTIAL => 'مدفوع جزئياً',
            self::PAID => 'مدفوع بالكامل',
            self::OVERDUE => 'متأخر',
            self::CANCELLED => 'ملغي',
            self::REFUNDED => 'مسترد',
        };
    }
    
    /**
     * Get status label (English).
     */
    public function labelEn(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::SENT => 'Sent',
            self::VIEWED => 'Viewed',
            self::PARTIAL => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }
    
    /**
     * Get status color (for UI).
     */
    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::SENT => 'blue',
            self::VIEWED => 'indigo',
            self::PARTIAL => 'orange',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'purple',
        };
    }
    
    /**
     * Check if status allows editing.
     */
    public function allowsEditing(): bool
    {
        return match($this) {
            self::DRAFT => true,
            self::PENDING => true,
            self::SENT => false, // Requires special permission
            self::VIEWED => false,
            self::PARTIAL => false,
            self::PAID => false,
            self::OVERDUE => false,
            self::CANCELLED => false,
            self::REFUNDED => false,
        };
    }
    
    /**
     * Check if status allows deletion.
     */
    public function allowsDeletion(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING, self::CANCELLED]);
    }
    
    /**
     * Check if status is final (cannot be changed).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED, self::REFUNDED]);
    }
    
    /**
     * Check if status is active (not final or cancelled).
     */
    public function isActive(): bool
    {
        return !in_array($this, [self::PAID, self::CANCELLED, self::REFUNDED]);
    }
    
    /**
     * Get allowed next statuses.
     */
    public function allowedNextStatuses(): array
    {
        return match($this) {
            self::DRAFT => [self::PENDING, self::SENT, self::CANCELLED],
            self::PENDING => [self::SENT, self::CANCELLED],
            self::SENT => [self::VIEWED, self::PARTIAL, self::PAID, self::OVERDUE, self::CANCELLED],
            self::VIEWED => [self::PARTIAL, self::PAID, self::OVERDUE],
            self::PARTIAL => [self::PAID, self::OVERDUE],
            self::OVERDUE => [self::PARTIAL, self::PAID, self::CANCELLED],
            self::PAID => [self::REFUNDED],
            self::CANCELLED => [],
            self::REFUNDED => [],
        };
    }
    
    /**
     * Check if transition to new status is allowed.
     */
    public function canTransitionTo(InvoiceStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedNextStatuses());
    }
    
    /**
     * Get status from string value.
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }
}

