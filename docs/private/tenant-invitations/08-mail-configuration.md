# Mail Configuration - Ownership-Specific SMTP

## Overview

Each ownership can have its own SMTP mail configuration. When sending emails related to an ownership (like tenant invitations), the system automatically uses that ownership's mail settings if configured. Otherwise, it falls back to the system default mail configuration.

**Important:** System emails (like email verification) always use the system default mail configuration, not ownership-specific settings.

---

## How It Works

### Mail Service: `OwnershipMailService`

**Location:** `app/Services/V1/Mail/OwnershipMailService.php`

**Responsibilities:**
- Retrieves ownership-specific mail settings
- Creates dynamic mailers per ownership
- Validates SMTP settings
- Sends emails using ownership-specific mailer

### Flow

```
Send Email Request
  ↓
OwnershipMailService.getMailerForOwnership(ownershipId)
  ↓
Check if ownership has mail settings
  ↓
Has Settings? → Create dynamic mailer → Use ownership mailer
  ↓
No Settings? → Use system default mailer
  ↓
Send Email
```

---

## Mail Settings Storage

### Database Table: `system_settings`

Mail settings are stored in the `system_settings` table with:
- `ownership_id`: The ownership ID (NOT NULL for ownership-specific)
- `group`: `'notification'`
- `key`: One of the mail setting keys
- `value`: The setting value
- `value_type`: The type of value (`string`, `integer`, `boolean`, etc.)

### Setting Keys

| Key | Description | Value Type | Required | Default |
|-----|-------------|------------|----------|---------|
| `smtp_host` | SMTP server hostname | string | Yes | - |
| `smtp_port` | SMTP server port | integer | Yes | - |
| `smtp_username` | SMTP username | string | Yes | - |
| `smtp_password` | SMTP password | string | Yes | - |
| `smtp_encryption` | Encryption type (`tls` or `ssl`) | string | No | `tls` |
| `email_from_address` | From email address | string | No | System default |
| `email_from_name` | From name | string | No | System default |

---

## Configuration Methods

### Method 1: Via API

**Endpoint:** `POST /api/v1/settings`

**Request:**
```json
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

**Headers:**
```
Authorization: Bearer {token}
Cookie: ownership_uuid={ownership_uuid}
```

---

### Method 2: Via Database

**SQL:**
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

### Method 3: Via Seeder

**Seeder:** `database/seeders/V1/Setting/SystemSettingSeeder.php`

The seeder creates mail settings with `null` values by default. Owners must configure them via API or database.

---

## Dynamic Mailer Creation

### Process

1. **Check Settings**
   ```php
   $settings = $this->getOwnershipMailSettings($ownershipId);
   ```

2. **Validate Settings**
   ```php
   if (!$this->hasValidSmtpSettings($settings)) {
       return config('mail.default'); // Use system default
   }
   ```

3. **Create Mailer**
   ```php
   $mailerName = "ownership_{$ownershipId}";
   $this->configureOwnershipMailer($mailerName, $settings);
   ```

4. **Configure Mailer**
   ```php
   Config::set("mail.mailers.{$mailerName}", [
       'transport' => 'smtp',
       'host' => $settings['smtp_host'],
       'port' => (int) $settings['smtp_port'],
       'username' => $settings['smtp_username'],
       'password' => $settings['smtp_password'],
       'encryption' => $settings['smtp_encryption'] ?? 'tls',
       ...
   ]);
   ```

5. **Use Mailer**
   ```php
   Mail::mailer($mailerName)->to($email)->send($mailable);
   ```

---

## Email Types

### Ownership-Specific Emails

These emails use ownership-specific mail configuration if available:

✅ **Tenant Invitation Emails**
- Sent from `TenantInvitationService`
- Uses `OwnershipMailService`
- Falls back to system default if no ownership settings

✅ **Future: Invoice Reminder Emails**
✅ **Future: Payment Reminder Emails**
✅ **Future: Contract-Related Emails**

### System Emails

These emails **always** use the system default mail configuration:

✅ **Email Verification Emails**
- Sent from `AuthService`
- Uses system default mailer
- Not ownership-scoped

✅ **Password Reset Emails**
✅ **System Notifications**
✅ **Admin Notifications**

---

## Fallback Behavior

### Scenario 1: Ownership Has Custom Settings

```
Ownership Settings Exist → Use Ownership Mailer → Send Email
```

### Scenario 2: Ownership Has No Settings

```
No Ownership Settings → Use System Default Mailer → Send Email
```

### Scenario 3: Ownership Settings Invalid

```
Invalid Settings (missing required fields) → Use System Default Mailer → Send Email
```

### Scenario 4: System Default Fails

```
System Default Fails → Laravel Error Handling → Log Error
```

---

## Validation

### Required Settings Check

```php
private function hasValidSmtpSettings(array $settings): bool
{
    $required = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password'];
    
    foreach ($required as $key) {
        if (empty($settings[$key])) {
            return false;
        }
    }
    
    return true;
}
```

**All four required settings must be present and non-empty.**

---

## Usage Examples

### In TenantInvitationService

```php
private function sendInvitationEmail(TenantInvitation $invitation): void
{
    // Use ownership-specific mailer if configured
    $this->mailService->sendForOwnership(
        $invitation->ownership_id,
        $invitation->email,
        new TenantInvitationMail($invitation)
    );
}
```

### Direct Usage

```php
use App\Services\V1\Mail\OwnershipMailService;

$mailService = app(OwnershipMailService::class);

// Send email using ownership mailer
$mailService->sendForOwnership(
    $ownershipId,
    'tenant@example.com',
    new TenantInvitationMail($invitation)
);
```

---

## Testing

### Test Ownership Mail Configuration

```php
use App\Services\V1\Mail\OwnershipMailService;

$mailService = app(OwnershipMailService::class);

// Test getting mailer
$mailer = $mailService->getMailerForOwnership(1);
echo "Mailer: {$mailer}"; 
// Output: "ownership_1" if configured, or "log"/"smtp" if not

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

### Password Storage

- SMTP passwords stored in `system_settings` table
- Consider encryption for sensitive passwords
- Use environment variables for system passwords

### Access Control

- Only users with `settings.notification.update` permission can modify mail settings
- Ownership-scoped: Users can only modify settings for their ownerships

### Validation

- Service validates required SMTP settings before using them
- Falls back to system default if settings invalid

---

## Troubleshooting

### Email Not Sending

**Check 1: Ownership Has Settings**
```sql
SELECT * FROM system_settings 
WHERE ownership_id = 1 AND `group` = 'notification' 
AND `key` LIKE 'smtp%';
```

**Check 2: Settings Are Valid**
- All required fields present
- Values are correct (host, port, username, password)

**Check 3: Laravel Logs**
```bash
tail -f storage/logs/laravel.log
```

**Check 4: Email Logs**
```bash
tail -f storage/logs/emails.log
```

### Using Wrong Mailer

**Check 1: Ownership ID**
- Verify correct ownership ID passed
- Check ownership scope middleware

**Check 2: Settings Existence**
- Check if ownership has custom settings
- If not, uses system default (expected behavior)

**Check 3: Settings Validity**
- Verify all required fields present
- Check for null/empty values

---

## Related Files

- **Service:** `app/Services/V1/Mail/OwnershipMailService.php`
- **Usage:** `app/Services/V1/Tenant/TenantInvitationService.php`
- **Seeder:** `database/seeders/V1/Setting/SystemSettingSeeder.php`
- **Config:** `config/mail.php`

---

## Related Documentation

- **[Overview](./01-overview.md)**
- **[Workflow - Owner](./05-workflow-owner.md)**
- **[Testing Guide](./11-testing-guide.md)**

