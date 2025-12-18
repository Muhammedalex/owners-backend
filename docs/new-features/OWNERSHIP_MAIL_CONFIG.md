# Ownership-Specific Mail Configuration

## Overview

Each ownership can have its own SMTP mail configuration. When sending emails related to an ownership (like tenant invitations), the system will automatically use that ownership's mail settings if configured. Otherwise, it falls back to the system default mail configuration.

**Important:** System emails (like email verification) always use the system default mail configuration, not ownership-specific settings.

---

## How It Works

### 1. Mail Settings Storage

Ownership-specific mail settings are stored in the `system_settings` table with:
- `ownership_id`: The ownership ID (NOT NULL for ownership-specific)
- `group`: `'notification'`
- `key`: One of the mail setting keys (see below)
- `value`: The setting value
- `value_type`: The type of value (`string`, `integer`, `boolean`, etc.)

### 2. Mail Setting Keys

| Key | Description | Value Type | Required |
|-----|-------------|------------|----------|
| `smtp_host` | SMTP server hostname | string | Yes |
| `smtp_port` | SMTP server port | integer | Yes |
| `smtp_username` | SMTP username | string | Yes |
| `smtp_password` | SMTP password | string | Yes |
| `smtp_encryption` | Encryption type (`tls` or `ssl`) | string | No (defaults to `tls`) |
| `email_from_address` | From email address | string | No |
| `email_from_name` | From name | string | No |

### 3. Dynamic Mailer Creation

When sending an ownership-related email:
1. System checks if ownership has custom mail settings
2. If valid SMTP settings exist, creates a dynamic mailer named `ownership_{ownership_id}`
3. Uses that mailer to send the email
4. If no custom settings, uses default system mailer

---

## Implementation

### Service: `OwnershipMailService`

Located at: `app/Services/V1/Mail/OwnershipMailService.php`

**Key Methods:**
- `getMailerForOwnership(?int $ownershipId)`: Returns mailer name to use
- `sendForOwnership(?int $ownershipId, string $to, Mailable $mailable)`: Sends email using ownership-specific mailer

### Usage Example

```php
use App\Services\V1\Mail\OwnershipMailService;
use App\Mail\V1\Tenant\TenantInvitationMail;

// In your service/controller
$mailService->sendForOwnership(
    $ownershipId,
    $email,
    new TenantInvitationMail($invitation)
);
```

---

## Setting Up Ownership Mail Configuration

### Via API (Recommended)

```bash
POST /api/v1/settings
Authorization: Bearer {token}
Cookie: ownership_uuid={ownership_uuid}

{
  "settings": [
    {
      "key": "smtp_host",
      "value": "smtp.example.com",
      "value_type": "string",
      "group": "notification"
    },
    {
      "key": "smtp_port",
      "value": "587",
      "value_type": "integer",
      "group": "notification"
    },
    {
      "key": "smtp_username",
      "value": "noreply@example.com",
      "value_type": "string",
      "group": "notification"
    },
    {
      "key": "smtp_password",
      "value": "your-password",
      "value_type": "string",
      "group": "notification"
    },
    {
      "key": "smtp_encryption",
      "value": "tls",
      "value_type": "string",
      "group": "notification"
    },
    {
      "key": "email_from_address",
      "value": "noreply@example.com",
      "value_type": "string",
      "group": "notification"
    },
    {
      "key": "email_from_name",
      "value": "ABC Real Estate",
      "value_type": "string",
      "group": "notification"
    }
  ]
}
```

### Via Database

```sql
INSERT INTO system_settings (ownership_id, `key`, value, value_type, `group`, description) VALUES
(1, 'smtp_host', 'smtp.example.com', 'string', 'notification', 'SMTP host'),
(1, 'smtp_port', '587', 'integer', 'notification', 'SMTP port'),
(1, 'smtp_username', 'noreply@example.com', 'string', 'notification', 'SMTP username'),
(1, 'smtp_password', 'your-password', 'string', 'notification', 'SMTP password'),
(1, 'smtp_encryption', 'tls', 'string', 'notification', 'SMTP encryption'),
(1, 'email_from_address', 'noreply@example.com', 'string', 'notification', 'From email address'),
(1, 'email_from_name', 'ABC Real Estate', 'string', 'notification', 'From name');
```

---

## Email Types

### Ownership-Specific Emails (Use Ownership Mail Config)

These emails use ownership-specific mail configuration if available:
- ✅ Tenant invitation emails
- ✅ Invoice reminder emails (future)
- ✅ Payment reminder emails (future)
- ✅ Contract-related emails (future)
- ✅ Any email sent from `TenantInvitationService` or other ownership-scoped services

### System Emails (Always Use System Config)

These emails **always** use the system default mail configuration:
- ✅ Email verification emails
- ✅ Password reset emails
- ✅ System notifications
- ✅ Admin notifications

---

## Fallback Behavior

1. **If ownership has custom SMTP settings:**
   - Uses ownership-specific mailer
   - Falls back to system default if settings are invalid/incomplete

2. **If ownership has no custom settings:**
   - Uses system default mailer (`config('mail.default')`)

3. **If system default mailer fails:**
   - Laravel's default error handling applies

---

## Testing

### Test Ownership Mail Configuration

```php
use App\Services\V1\Mail\OwnershipMailService;

$mailService = app(OwnershipMailService::class);

// Test getting mailer for ownership
$mailer = $mailService->getMailerForOwnership(1);
echo "Mailer: {$mailer}"; // Should be 'ownership_1' if configured, or 'log'/'smtp' if not

// Test sending email
$mailService->sendForOwnership(
    1,
    'test@example.com',
    new TenantInvitationMail($invitation)
);
```

### Check Email Logs

```bash
# Check if email was sent with ownership-specific config
tail -f storage/logs/emails.log
```

---

## Security Considerations

1. **Password Storage:** SMTP passwords are stored in `system_settings` table. Consider encryption for sensitive passwords.

2. **Access Control:** Only users with `settings.notification.update` permission can modify mail settings.

3. **Validation:** The service validates that required SMTP settings are present before using them.

---

## Troubleshooting

### Email Not Sending

1. Check if ownership has mail settings configured:
   ```sql
   SELECT * FROM system_settings 
   WHERE ownership_id = 1 AND `group` = 'notification';
   ```

2. Verify SMTP settings are correct (host, port, username, password)

3. Check Laravel logs: `storage/logs/laravel.log`

4. Check email logs: `storage/logs/emails.log`

### Using Wrong Mailer

1. Verify ownership ID is passed correctly
2. Check if ownership has custom settings (if not, uses default)
3. Verify settings are valid (all required fields present)

---

## Future Enhancements

- [ ] Email template customization per ownership
- [ ] Email signature per ownership
- [ ] Email delivery tracking
- [ ] Email bounce handling
- [ ] Multiple SMTP accounts per ownership (failover)

