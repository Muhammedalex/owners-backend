# Feature 3: Automated Invoice Generation & Reminders

## Overview

Automatically generate invoices based on contract payment frequency and send reminders to tenants and payment collectors. Includes flexible multi-channel notification system (email, real-time, SMS-ready).

---

## Business Requirements

### User Stories

1. **As an Owner**, I want invoices to be generated automatically based on contract schedules.
2. **As a Tenant**, I want to receive reminders before payment is due.
3. **As a Payment Collector**, I want to be notified when invoices are generated and when payments are due.
4. **As a System**, I want to send notifications via multiple channels (email, real-time, SMS) based on ownership preferences.

### Key Requirements

- ✅ Automated invoice generation based on contract payment frequency
- ✅ Configurable reminder schedule (before due date)
- ✅ Multi-channel notifications (email, real-time, SMS-ready)
- ✅ Payment collector role assignment
- ✅ Ownership-level notification preferences
- ✅ Flexible notification service architecture
- ✅ Reminder escalation (multiple reminders before due date)

---

## Workflow

### 1. Automated Invoice Generation

```
Scheduled Job (Daily at 2:00 AM)
  ↓
Find contracts with:
  - Status: active
  - Payment frequency matches today
  - No invoice exists for current period
  ↓
For each contract:
  - Calculate billing period
  - Generate invoice number
  - Create invoice (status: draft)
  - Create invoice items (rent, utilities, etc.)
  - Calculate tax (15% VAT)
  - Set due date
  - Mark as "sent"
  ↓
Send notifications:
  - Tenant: Invoice generated notification
  - Payment Collector: Invoice ready for collection
  - Owner: Invoice generated summary
```

### 2. Reminder System

```
Scheduled Job (Daily at 9:00 AM)
  ↓
Find invoices:
  - Status: sent (not paid)
  - Due date approaching (based on reminder schedule)
  ↓
For each invoice:
  - Check ownership reminder settings
  - Determine which reminders already sent
  - Send next reminder if due
  ↓
Send notifications:
  - Tenant: Payment reminder
  - Payment Collector: Collection reminder
  ↓
Log reminder sent
```

### 3. Payment Collection Workflow

```
Payment Collector logs in
  ↓
Sees dashboard with:
  - Pending invoices
  - Upcoming due dates
  - Overdue invoices
  ↓
Collects payment from tenant
  ↓
Records payment in system
  ↓
System updates invoice status
  ↓
Sends confirmation:
  - Tenant: Payment received
  - Owner: Payment recorded
```

---

## Database Design

### New Table: `invoice_reminders`

Track reminder history for each invoice.

```sql
CREATE TABLE invoice_reminders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    invoice_id BIGINT NOT NULL,
    reminder_type VARCHAR(50) NOT NULL, -- 'before_due', 'on_due', 'overdue'
    days_before_due INT NULLABLE, -- Days before due date (for before_due type)
    sent_at TIMESTAMP NOT NULL,
    sent_to_user_id BIGINT NULLABLE, -- Tenant or collector who received reminder
    channel VARCHAR(50) NOT NULL, -- 'email', 'sms', 'push', 'realtime'
    status VARCHAR(50) DEFAULT 'sent', -- 'sent', 'failed', 'pending'
    error_message TEXT NULLABLE,
    created_at TIMESTAMP,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_to_user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_channel (channel),
    INDEX idx_status (status)
);
```

### New Table: `payment_collectors`

Assign users as payment collectors for ownerships.

```sql
CREATE TABLE payment_collectors (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ownership_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    assigned_by BIGINT NOT NULL, -- User who assigned this collector
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (ownership_id) REFERENCES ownerships(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_collector (ownership_id, user_id),
    INDEX idx_ownership_id (ownership_id),
    INDEX idx_user_id (user_id),
    INDEX idx_active (active)
);
```

### Update Table: `system_settings`

Add ownership-level notification preferences.

**New Settings Keys:**
- `invoices.auto_generate` (boolean)
- `invoices.reminder_days` (array: [7, 3, 1]) - Days before due date
- `invoices.reminder_channels` (array: ['email', 'realtime'])
- `invoices.sms_enabled` (boolean)
- `invoices.email_template` (string: template name)
- `invoices.overdue_reminder_enabled` (boolean)
- `invoices.overdue_reminder_frequency` (integer: days)

---

## Models

### InvoiceReminder Model

**File:** `app/Models/V1/Invoice/InvoiceReminder.php`

```php
<?php

namespace App\Models\V1\Invoice;

use App\Models\V1\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReminder extends Model
{
    protected $table = 'invoice_reminders';

    protected $fillable = [
        'invoice_id',
        'reminder_type',
        'days_before_due',
        'sent_at',
        'sent_to_user_id',
        'channel',
        'status',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function sentTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_to_user_id');
    }
}
```

### PaymentCollector Model

**File:** `app/Models/V1/Ownership/PaymentCollector.php`

```php
<?php

namespace App\Models\V1\Ownership;

use App\Models\V1\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentCollector extends Model
{
    protected $table = 'payment_collectors';

    protected $fillable = [
        'ownership_id',
        'user_id',
        'active',
        'assigned_by',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
```

### Ownership Model Updates

**File:** `app/Models/V1/Ownership/Ownership.php`

**Add Relationship:**

```php
/**
 * Get payment collectors for this ownership.
 */
public function paymentCollectors(): HasMany
{
    return $this->hasMany(PaymentCollector::class, 'ownership_id')
        ->where('active', true);
}
```

---

## Services

### AutomatedInvoiceService

**File:** `app/Services/V1/Invoice/AutomatedInvoiceService.php`

**Key Methods:**

```php
class AutomatedInvoiceService
{
    /**
     * Generate invoices for contracts due today.
     */
    public function generateInvoicesForDueContracts(): int
    {
        $contracts = $this->getContractsDueForInvoicing();
        $generated = 0;

        foreach ($contracts as $contract) {
            try {
                $this->generateInvoiceForContract($contract);
                $generated++;
            } catch (\Exception $e) {
                \Log::error("Failed to generate invoice for contract {$contract->id}: " . $e->getMessage());
            }
        }

        return $generated;
    }

    /**
     * Generate invoice for a specific contract.
     */
    public function generateInvoiceForContract(Contract $contract): Invoice
    {
        $period = $this->calculateBillingPeriod($contract);
        
        $invoice = $this->invoiceService->create([
            'contract_id' => $contract->id,
            'ownership_id' => $contract->ownership_id,
            'number' => $this->generateInvoiceNumber($contract->ownership_id),
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'due' => $period['due'],
            'amount' => $contract->rent,
            'tax_rate' => 15.00,
            'status' => 'sent',
            'generated_by' => null, // System generated
            'generated_at' => now(),
        ]);

        // Create invoice items
        $this->createInvoiceItems($invoice, $contract);

        // Send notifications
        $this->notificationService->sendInvoiceGeneratedNotifications($invoice);

        return $invoice;
    }

    /**
     * Calculate billing period based on payment frequency.
     */
    private function calculateBillingPeriod(Contract $contract): array
    {
        $lastInvoice = $contract->invoices()->latest('period_end')->first();
        $startDate = $lastInvoice ? $lastInvoice->period_end->addDay() : $contract->start;
        
        $endDate = match($contract->payment_frequency) {
            'monthly' => $startDate->copy()->addMonth()->subDay(),
            'quarterly' => $startDate->copy()->addMonths(3)->subDay(),
            'yearly' => $startDate->copy()->addYear()->subDay(),
            'weekly' => $startDate->copy()->addWeek()->subDay(),
            default => $startDate->copy()->addMonth()->subDay(),
        };

        // Due date: end of period + grace period (default: 7 days)
        $dueDate = $endDate->copy()->addDays(7);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'due' => $dueDate,
        ];
    }
}
```

### InvoiceReminderService

**File:** `app/Services/V1/Invoice/InvoiceReminderService.php`

**Key Methods:**

```php
class InvoiceReminderService
{
    /**
     * Send reminders for invoices due soon.
     */
    public function sendDueReminders(): int
    {
        $invoices = $this->getInvoicesNeedingReminders();
        $sent = 0;

        foreach ($invoices as $invoice) {
            try {
                $this->sendReminderForInvoice($invoice);
                $sent++;
            } catch (\Exception $e) {
                \Log::error("Failed to send reminder for invoice {$invoice->id}: " . $e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Send reminder for a specific invoice.
     */
    public function sendReminderForInvoice(Invoice $invoice): void
    {
        $ownership = $invoice->ownership;
        $settings = $this->getReminderSettings($ownership);
        
        $daysUntilDue = now()->diffInDays($invoice->due, false);
        
        // Determine which reminder to send
        $reminderType = $this->determineReminderType($daysUntilDue, $settings);
        
        if (!$reminderType) {
            return; // No reminder needed
        }

        // Check if already sent
        if ($this->reminderAlreadySent($invoice, $reminderType)) {
            return;
        }

        // Send via configured channels
        $channels = $settings['reminder_channels'] ?? ['email', 'realtime'];
        
        foreach ($channels as $channel) {
            $this->sendReminderViaChannel($invoice, $reminderType, $channel, $daysUntilDue);
        }
    }

    /**
     * Determine reminder type based on days until due.
     */
    private function determineReminderType(int $daysUntilDue, array $settings): ?string
    {
        $reminderDays = $settings['reminder_days'] ?? [7, 3, 1];
        
        if ($daysUntilDue < 0) {
            return 'overdue';
        }
        
        foreach ($reminderDays as $days) {
            if ($daysUntilDue == $days) {
                return "before_due_{$days}";
            }
        }
        
        return null;
    }
}
```

### NotificationService (Enhanced)

**File:** `app/Services/V1/Notification/NotificationService.php`

**New Methods:**

```php
class NotificationService
{
    /**
     * Send invoice generated notifications.
     */
    public function sendInvoiceGeneratedNotifications(Invoice $invoice): void
    {
        $ownership = $invoice->ownership;
        $contract = $invoice->contract;
        $tenant = $contract->tenant;
        
        // Notify tenant
        $this->sendToTenant($invoice, $tenant, 'invoice.generated');
        
        // Notify payment collectors
        $collectors = $ownership->paymentCollectors;
        foreach ($collectors as $collector) {
            $this->sendToCollector($invoice, $collector->user, 'invoice.ready_for_collection');
        }
        
        // Notify owner (optional)
        if ($settings['notify_owner'] ?? true) {
            $this->sendToOwner($invoice, $ownership);
        }
    }

    /**
     * Send reminder notifications.
     */
    public function sendReminderNotifications(Invoice $invoice, string $reminderType, int $daysUntilDue): void
    {
        $ownership = $invoice->ownership;
        $contract = $invoice->contract;
        $tenant = $contract->tenant;
        $settings = $this->getNotificationSettings($ownership);
        
        // Determine channels
        $channels = $settings['reminder_channels'] ?? ['email', 'realtime'];
        
        // Notify tenant
        foreach ($channels as $channel) {
            $this->sendViaChannel(
                $tenant->user,
                'invoice.reminder',
                [
                    'invoice' => $invoice,
                    'reminder_type' => $reminderType,
                    'days_until_due' => $daysUntilDue,
                ],
                $channel
            );
        }
        
        // Notify collectors
        $collectors = $ownership->paymentCollectors;
        foreach ($collectors as $collector) {
            foreach ($channels as $channel) {
                $this->sendViaChannel(
                    $collector->user,
                    'invoice.collection_reminder',
                    [
                        'invoice' => $invoice,
                        'reminder_type' => $reminderType,
                        'days_until_due' => $daysUntilDue,
                    ],
                    $channel
                );
            }
        }
    }

    /**
     * Send notification via specific channel.
     */
    public function sendViaChannel(User $user, string $type, array $data, string $channel): bool
    {
        return match($channel) {
            'email' => $this->sendEmail($user, $type, $data),
            'realtime' => $this->sendRealtime($user, $type, $data),
            'sms' => $this->sendSMS($user, $type, $data),
            default => false,
        };
    }

    /**
     * Send email notification.
     */
    private function sendEmail(User $user, string $type, array $data): bool
    {
        try {
            Mail::to($user->email)->send(
                new InvoiceNotificationMail($type, $data)
            );
            return true;
        } catch (\Exception $e) {
            \Log::error("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send real-time notification.
     */
    private function sendRealtime(User $user, string $type, array $data): bool
    {
        try {
            $notification = $this->create([
                'user_id' => $user->id,
                'type' => 'info',
                'title' => $this->getNotificationTitle($type, $data),
                'message' => $this->getNotificationMessage($type, $data),
                'category' => 'invoice',
                'data' => $data,
                'action_url' => $this->getActionUrl($type, $data),
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Realtime notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS notification (future implementation).
     */
    private function sendSMS(User $user, string $type, array $data): bool
    {
        // TODO: Implement SMS service
        // This will use a service interface that can be swapped
        // For now, return false (not implemented)
        return false;
    }
}
```

---

## Notification Channels Architecture

### Channel Interface

**File:** `app/Services/V1/Notification/Channels/NotificationChannelInterface.php`

```php
<?php

namespace App\Services\V1\Notification\Channels;

use App\Models\V1\Auth\User;

interface NotificationChannelInterface
{
    public function send(User $user, string $type, array $data): bool;
    public function isEnabled(array $settings): bool;
    public function getName(): string;
}
```

### Email Channel

**File:** `app/Services/V1/Notification/Channels/EmailChannel.php`

```php
class EmailChannel implements NotificationChannelInterface
{
    public function send(User $user, string $type, array $data): bool
    {
        // Implementation
    }

    public function isEnabled(array $settings): bool
    {
        return $settings['email_enabled'] ?? true;
    }

    public function getName(): string
    {
        return 'email';
    }
}
```

### Realtime Channel

**File:** `app/Services/V1/Notification/Channels/RealtimeChannel.php`

```php
class RealtimeChannel implements NotificationChannelInterface
{
    public function send(User $user, string $type, array $data): bool
    {
        // Implementation using Laravel Broadcasting
    }

    public function isEnabled(array $settings): bool
    {
        return $settings['realtime_enabled'] ?? true;
    }

    public function getName(): string
    {
        return 'realtime';
    }
}
```

### SMS Channel (Future)

**File:** `app/Services/V1/Notification/Channels/SMSChannel.php`

```php
class SMSChannel implements NotificationChannelInterface
{
    public function send(User $user, string $type, array $data): bool
    {
        // TODO: Implement SMS service
        // Will use ownership settings to determine SMS provider
    }

    public function isEnabled(array $settings): bool
    {
        return $settings['sms_enabled'] ?? false;
    }

    public function getName(): string
    {
        return 'sms';
    }
}
```

---

## Scheduled Jobs

### Generate Invoices Job

**File:** `app/Console/Commands/GenerateInvoices.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\V1\Invoice\AutomatedInvoiceService;
use Illuminate\Console\Command;

class GenerateInvoices extends Command
{
    protected $signature = 'invoices:generate';
    protected $description = 'Generate invoices for contracts due today';

    public function handle(AutomatedInvoiceService $service): int
    {
        $this->info('Generating invoices...');
        
        $count = $service->generateInvoicesForDueContracts();
        
        $this->info("Generated {$count} invoices.");
        
        return Command::SUCCESS;
    }
}
```

### Send Reminders Job

**File:** `app/Console/Commands/SendInvoiceReminders.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\V1\Invoice\InvoiceReminderService;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders';
    protected $description = 'Send payment reminders for due invoices';

    public function handle(InvoiceReminderService $service): int
    {
        $this->info('Sending reminders...');
        
        $count = $service->sendDueReminders();
        
        $this->info("Sent {$count} reminders.");
        
        return Command::SUCCESS;
    }
}
```

### Schedule Registration

**File:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    // Generate invoices daily at 2:00 AM
    $schedule->command('invoices:generate')
        ->dailyAt('02:00')
        ->timezone('Asia/Riyadh');

    // Send reminders daily at 9:00 AM
    $schedule->command('invoices:send-reminders')
        ->dailyAt('09:00')
        ->timezone('Asia/Riyadh');
}
```

---

## API Endpoints

### Payment Collector Management

```
POST   /api/v1/ownerships/{uuid}/payment-collectors
GET    /api/v1/ownerships/{uuid}/payment-collectors
DELETE /api/v1/ownerships/{uuid}/payment-collectors/{collector}
POST   /api/v1/ownerships/{uuid}/payment-collectors/{collector}/activate
POST   /api/v1/ownerships/{uuid}/payment-collectors/{collector}/deactivate
```

### Notification Settings

```
GET    /api/v1/ownerships/{uuid}/notification-settings
PUT    /api/v1/ownerships/{uuid}/notification-settings
```

### Manual Invoice Generation

```
POST   /api/v1/contracts/{uuid}/generate-invoice
```

---

## Email Templates

### Invoice Generated (Tenant)

**Subject:** `New Invoice Generated - {Invoice Number}`

**Template:** `resources/views/emails/invoices/generated-tenant.blade.php`

### Invoice Reminder (Tenant)

**Subject:** `Payment Reminder - Invoice {Invoice Number} Due in {Days} Days`

**Template:** `resources/views/emails/invoices/reminder-tenant.blade.php`

### Invoice Ready for Collection (Collector)

**Subject:** `New Invoice Ready for Collection - {Invoice Number}`

**Template:** `resources/views/emails/invoices/ready-collector.blade.php`

---

## Implementation Checklist

- [ ] Create migrations (invoice_reminders, payment_collectors)
- [ ] Create models (InvoiceReminder, PaymentCollector)
- [ ] Create AutomatedInvoiceService
- [ ] Create InvoiceReminderService
- [ ] Enhance NotificationService with channel system
- [ ] Create notification channel interfaces and implementations
- [ ] Create scheduled jobs
- [ ] Create email templates
- [ ] Create API endpoints for collectors
- [ ] Create API endpoints for notification settings
- [ ] Add system settings for notification preferences
- [ ] Write tests
- [ ] Update API documentation
- [ ] Frontend integration

---

## Future Enhancements

1. **SMS Integration:** Implement SMS service with provider selection
2. **WhatsApp Notifications:** Add WhatsApp channel
3. **Custom Reminder Schedules:** Per-contract reminder settings
4. **Reminder Templates:** Customizable email/SMS templates
5. **Payment Links:** Generate payment links in reminders
6. **Analytics:** Track reminder effectiveness
7. **Multi-language:** Support Arabic/English in notifications

