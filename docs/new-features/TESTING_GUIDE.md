# Testing Guide - Tenant Self-Registration

## Email Logging Setup

Emails are logged to a **separate private file** for development:
- **Location:** `storage/logs/emails.log`
- **Configuration:** Already set to use `log` driver with `emails` channel
- **No real emails sent** - All emails are logged to file only

---

## Testing Methods

### Method 1: Artisan Command (Recommended)

#### Test Single Invitation
```bash
php artisan test:tenant-invitation --email=test@example.com
```

#### Test with Specific Ownership & User
```bash
php artisan test:tenant-invitation \
  --email=test@example.com \
  --ownership=your-ownership-uuid \
  --user=1
```

#### Test Bulk Invitations
```bash
php artisan test:tenant-invitation --bulk
```

#### Test Generate Link (No Email)
```bash
php artisan test:tenant-invitation --link --email=test@example.com
```

---

### Method 2: API Test Endpoints (Development Only)

These endpoints are **only available in non-production environments**.

#### Test Single Invitation
```bash
POST /api/v1/test/tenant-invitations/create
Content-Type: application/json

{
  "email": "test@example.com",
  "name": "Test Tenant",
  "ownership_uuid": "optional-uuid",
  "user_id": 1,
  "expires_in_days": 7
}
```

**Response:**
```json
{
  "success": true,
  "message": "Invitation created successfully. Check storage/logs/emails.log for email content.",
  "data": {
    "invitation": {
      "uuid": "...",
      "email": "test@example.com",
      "token": "...",
      "status": "pending",
      "expires_at": "2025-12-22 10:00:00",
      "invitation_url": "http://localhost:3000/register/tenant?token=..."
    },
    "log_file": "M:\\2025\\ivision\\Saas\\system\\owners\\storage\\logs\\emails.log"
  }
}
```

#### Test Bulk Invitations
```bash
POST /api/v1/test/tenant-invitations/bulk
Content-Type: application/json

{
  "invitations": [
    {
      "email": "tenant1@example.com",
      "name": "Tenant One"
    },
    {
      "email": "tenant2@example.com",
      "name": "Tenant Two"
    }
  ],
  "ownership_uuid": "optional-uuid",
  "user_id": 1
}
```

#### Test Generate Link
```bash
POST /api/v1/test/tenant-invitations/generate-link
Content-Type: application/json

{
  "email": "test@example.com",
  "name": "Test Tenant"
}
```

---

### Method 3: Using Real API Endpoints (With Authentication)

#### 1. Get Auth Token
```bash
POST /api/v1/auth/login
{
  "email": "owner@example.com",
  "password": "password"
}
```

#### 2. Create Invitation
```bash
POST /api/v1/tenants/invitations
Authorization: Bearer {token}
Cookie: ownership_uuid={ownership-uuid}

{
  "email": "tenant@example.com",
  "name": "Test Tenant",
  "expires_in_days": 7
}
```

#### 3. Validate Token (Public)
```bash
GET /api/v1/public/tenant-invitations/{token}/validate
```

#### 4. Accept Invitation (Public)
```bash
POST /api/v1/public/tenant-invitations/{token}/accept
{
  "first_name": "Ahmed",
  "last_name": "Ali",
  "email": "tenant@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "national_id": "1234567890"
}
```

---

## Viewing Email Logs

### Windows PowerShell
```powershell
Get-Content storage\logs\emails.log -Tail 50
```

### Windows CMD
```cmd
type storage\logs\emails.log
```

### Linux/Mac
```bash
tail -f storage/logs/emails.log
```

---

## Email Log Format

The email log contains:
- **Timestamp**
- **Email To:** Recipient email address
- **Email Subject:** Invitation subject
- **Email Body:** Full HTML email content
- **Invitation Details:** Token, URL, etc.

**Example Log Entry:**
```
[2025-12-15 10:00:00] local.INFO: Email sent to test@example.com
Subject: You're invited to register as a tenant - ABC Real Estate
Body: [Full HTML email content]
```

---

## Testing Checklist

### ✅ Basic Functionality
- [ ] Create single invitation (sends email)
- [ ] Create bulk invitations (sends multiple emails)
- [ ] Generate link (no email)
- [ ] View invitation list
- [ ] View single invitation
- [ ] Resend invitation
- [ ] Cancel invitation

### ✅ Email Testing
- [ ] Check `storage/logs/emails.log` file exists
- [ ] Verify email content in log file
- [ ] Verify invitation URL in email
- [ ] Verify expiration date in email
- [ ] Test Arabic email content
- [ ] Test English email content

### ✅ Token Validation
- [ ] Validate valid token
- [ ] Validate expired token
- [ ] Validate cancelled token
- [ ] Validate already accepted token
- [ ] Validate invalid token

### ✅ Registration Flow
- [ ] Accept invitation with new user
- [ ] Accept invitation with existing user
- [ ] Verify tenant profile created
- [ ] Verify user account created
- [ ] Verify invitation marked as accepted
- [ ] Test duplicate email prevention
- [ ] Test email mismatch validation

### ✅ Edge Cases
- [ ] Test with missing ownership
- [ ] Test with missing user
- [ ] Test with invalid email format
- [ ] Test with expired invitation
- [ ] Test with cancelled invitation
- [ ] Test bulk with mixed valid/invalid emails

---

## Quick Test Script

Create a simple test file: `test-invitation.php`

```php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test invitation creation
$service = app(\App\Services\V1\Tenant\TenantInvitationService::class);

$ownership = \App\Models\V1\Ownership\Ownership::first();
$user = \App\Models\V1\Auth\User::first();

$invitation = $service->create([
    'ownership_id' => $ownership->id,
    'invited_by' => $user->id,
    'email' => 'test@example.com',
    'name' => 'Test Tenant',
]);

echo "Invitation created!\n";
echo "UUID: {$invitation->uuid}\n";
echo "Token: {$invitation->token}\n";
echo "URL: {$invitation->getInvitationUrl()}\n";
echo "Check storage/logs/emails.log for email content\n";
```

Run:
```bash
php test-invitation.php
```

---

## Troubleshooting

### Email not appearing in log file
1. Check `config/mail.php` - should have `'default' => env('MAIL_MAILER', 'log')`
2. Check `config/logging.php` - should have `emails` channel configured
3. Check file permissions on `storage/logs/emails.log`
4. Check Laravel log for errors: `storage/logs/laravel.log`

### Invitation not created
1. Check database migration ran: `php artisan migrate`
2. Check ownership exists: `php artisan tinker` → `Ownership::count()`
3. Check user exists: `User::count()`
4. Check ownership scope middleware is working

### Token validation fails
1. Check token is correct (64 characters)
2. Check invitation not expired
3. Check invitation status is 'pending'
4. Check database for invitation record

---

## Next Steps After Testing

1. ✅ Verify all test cases pass
2. ✅ Check email content in log file
3. ✅ Test registration flow end-to-end
4. ✅ Verify tenant profile creation
5. ✅ Ready for frontend integration

---

**Email Log Location:** `storage/logs/emails.log`  
**Main Log Location:** `storage/logs/laravel.log`

