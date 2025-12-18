# Quick Test Guide - Tenant Invitations

## 🚀 Quick Start

### 1. Run Migration (When DB is Ready)
```bash
php artisan migrate
```

### 2. Test Single Invitation (Command Line)
```bash
php artisan test:tenant-invitation --email=test@example.com
```

### 3. Check Email Log
```powershell
Get-Content storage\logs\emails.log -Tail 20
```

---

## 📧 Email Logging Setup

✅ **Configured:**
- Emails logged to: `storage/logs/emails.log` (separate file)
- Mail driver: `log` (no real emails sent)
- Channel: `emails` (dedicated channel)

✅ **No Configuration Needed:**
- Already set in `config/mail.php`
- Already set in `config/logging.php`

---

## 🧪 Test Commands

### Single Invitation
```bash
php artisan test:tenant-invitation --email=tenant@example.com
```

### Bulk Invitations
```bash
php artisan test:tenant-invitation --bulk
```

### Generate Link Only
```bash
php artisan test:tenant-invitation --link --email=tenant@example.com
```

### With Options
```bash
php artisan test:tenant-invitation \
  --email=tenant@example.com \
  --ownership=uuid-here \
  --user=1
```

---

## 🌐 Test API Endpoints (Development Only)

### Create Invitation
```bash
POST http://localhost:8000/api/v1/test/tenant-invitations/create
Content-Type: application/json

{
  "email": "test@example.com",
  "name": "Test Tenant"
}
```

### Bulk Invitations
```bash
POST http://localhost:8000/api/v1/test/tenant-invitations/bulk
Content-Type: application/json

{
  "invitations": [
    {"email": "tenant1@example.com", "name": "One"},
    {"email": "tenant2@example.com", "name": "Two"}
  ]
}
```

### Generate Link
```bash
POST http://localhost:8000/api/v1/test/tenant-invitations/generate-link
Content-Type: application/json

{
  "email": "test@example.com"
}
```

---

## 📁 Files Created for Testing

1. ✅ `app/Console/Commands/TestTenantInvitation.php` - Artisan command
2. ✅ `app/Http/Controllers/Api/V1/Tenant/TestTenantInvitationController.php` - API endpoints
3. ✅ `config/logging.php` - Added `emails` channel
4. ✅ `config/mail.php` - Updated to use `emails` channel
5. ✅ `routes/api/v1/tenants.php` - Added test routes

---

## 📝 What Gets Logged

When an invitation email is sent, you'll see in `storage/logs/emails.log`:

```
[2025-12-15 10:00:00] local.INFO: Email sent to test@example.com
Subject: Invitation to join ABC Real Estate as a Tenant
Body: [Full HTML email with invitation link]
```

---

## ✅ Testing Checklist

- [ ] Run migration
- [ ] Test single invitation
- [ ] Test bulk invitations
- [ ] Test generate link
- [ ] Check email log file
- [ ] Verify invitation URL in log
- [ ] Test token validation
- [ ] Test registration acceptance

---

**Email Log:** `storage/logs/emails.log`  
**Main Log:** `storage/logs/laravel.log`

