# Troubleshooting Guide

## Overview

This guide provides solutions to common issues and errors encountered with the Tenant Invitation feature.

---

## Email Issues

### Issue: Email Not Sent

**Symptoms:**
- Invitation created but no email received
- Email not in `storage/logs/emails.log`

**Possible Causes & Solutions:**

#### 1. Mail Configuration

**Check:**
```bash
# Check mail config
php artisan tinker
>>> config('mail.default')
>>> config('mail.mailers.log')
```

**Solution:**
```env
# In .env
MAIL_MAILER=log
MAIL_LOG_CHANNEL=emails
```

#### 2. Mail Service Error

**Check Logs:**
```bash
tail -f storage/logs/laravel.log | grep -i mail
```

**Common Errors:**
- `Connection refused` - SMTP server down
- `Authentication failed` - Wrong credentials
- `SSL certificate problem` - SSL/TLS issue

**Solution:**
```bash
# Test mail service directly
php artisan tinker
>>> use App\Mail\V1\Tenant\TenantInvitationMail;
>>> use App\Models\V1\Tenant\TenantInvitation;
>>> $invitation = TenantInvitation::first();
>>> Mail::to('test@example.com')->send(new TenantInvitationMail($invitation));
```

#### 3. Ownership Mail Settings Missing

**Check:**
```sql
SELECT * FROM system_settings 
WHERE ownership_id = 1 
  AND `group` = 'notification' 
  AND `key` LIKE 'smtp%';
```

**Solution:**
- Ensure all required SMTP settings exist (host, port, username, password)
- Or set ownership settings to null to use system default

---

### Issue: Email Goes to Spam

**Symptoms:**
- Email delivered but in spam folder
- Email not visible in inbox

**Solutions:**

1. **SPF/DKIM Records:**
   - Configure SPF/DKIM for sending domain
   - Verify DNS records

2. **From Address:**
   - Use legitimate from address
   - Match domain with SMTP server

3. **Email Content:**
   - Avoid spam trigger words
   - Include unsubscribe link
   - Use proper HTML structure

---

## Token Issues

### Issue: Invalid Token Error

**Symptoms:**
- Error: "Invalid invitation token"
- Token not found in database

**Possible Causes & Solutions:**

#### 1. Token Copied Incorrectly

**Check:**
- Verify token in URL matches invitation
- Check for extra spaces or characters

#### 2. Token Expired

**Check:**
```sql
SELECT * FROM tenant_invitations 
WHERE token = '{token}' 
  AND expires_at > NOW();
```

**Solution:**
- Create new invitation
- Use longer expiration for future invitations

#### 3. Invitation Cancelled

**Check:**
```sql
SELECT status FROM tenant_invitations 
WHERE token = '{token}';
```

**Solution:**
- If cancelled, create new invitation
- Cannot reactivate cancelled invitations

---

### Issue: Token Already Used (Single-use)

**Symptoms:**
- Error: "Invitation has already been accepted"
- Status: `accepted` in database

**Solution:**
- This is expected behavior for single-use invitations
- Create new invitation if needed
- Or use multi-use invitation for multiple tenants

---

## Registration Issues

### Issue: Email Mismatch Error

**Symptoms:**
- Error: "Email does not match invitation"
- Registration fails even with correct data

**Cause:**
- Registration email doesn't match invitation email

**Solution:**
```bash
# Check invitation email
curl http://localhost:8000/api/v1/public/tenant-invitations/{token}/validate

# Use exact email from invitation
```

---

### Issue: Tenant Already Exists

**Symptoms:**
- Error: "Tenant already exists for this ownership"
- Cannot register again

**Cause:**
- User already has tenant profile for this ownership

**Solution:**
- User should login with existing account
- Cannot create duplicate tenant for same ownership

**Check:**
```sql
SELECT t.*, u.email
FROM tenants t
JOIN users u ON t.user_id = u.id
WHERE u.email = 'tenant@example.com'
  AND t.ownership_id = 1;
```

---

### Issue: Password Validation Error

**Symptoms:**
- Error: "The password must be at least 8 characters"
- Validation fails

**Requirements:**
- Minimum 8 characters
- Must be confirmed (password_confirmation must match)

**Solution:**
```json
{
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!"
}
```

---

## Permission Issues

### Issue: Unauthorized Error (403)

**Symptoms:**
- Error: "This action is unauthorized"
- Status: 403 Forbidden

**Possible Causes & Solutions:**

#### 1. Missing Permission

**Check User Permissions:**
```sql
SELECT p.name
FROM permissions p
JOIN model_has_permissions mp ON p.id = mp.permission_id
WHERE mp.model_id = {user_id}
  AND mp.model_type = 'App\\Models\\V1\\Auth\\User';
```

**Solution:**
- Assign required permission to user/role
- Required permissions: `tenants.invitations.view`, `tenants.invitations.create`, etc.

#### 2. Wrong Ownership Scope

**Check:**
```sql
SELECT * FROM user_ownership_mapping 
WHERE user_id = {user_id} 
  AND ownership_id = {ownership_id};
```

**Solution:**
- Ensure user has access to ownership
- Check `ownership_uuid` cookie is set correctly

---

### Issue: Cannot Close Multi-use Invitation

**Symptoms:**
- Error: "This action is unauthorized" when cancelling
- Invitation has no email/phone

**Cause:**
- Missing `tenants.invitations.close_without_contact` permission

**Solution:**
```php
// Assign special permission
$user->givePermissionTo('tenants.invitations.close_without_contact');
```

---

## Database Issues

### Issue: Foreign Key Constraint Error

**Symptoms:**
- Error: "FOREIGN KEY constraint failed"
- Cannot create invitation

**Possible Causes & Solutions:**

#### 1. Invalid Ownership ID

**Check:**
```sql
SELECT * FROM ownerships WHERE id = {ownership_id};
```

**Solution:**
- Use valid ownership ID
- Check ownership scope middleware

#### 2. Invalid User ID

**Check:**
```sql
SELECT * FROM users WHERE id = {user_id};
```

**Solution:**
- Ensure invited_by user exists
- Use authenticated user ID

---

### Issue: Duplicate Invitation

**Symptoms:**
- Error: "Duplicate entry for key 'token'"
- Invitation creation fails

**Cause:**
- Token collision (extremely rare)

**Solution:**
- System will regenerate token automatically
- No action needed

---

## Configuration Issues

### Issue: Email Verification Enabled but Not Working

**Symptoms:**
- Email verification notification not sent
- `email_verified_at` is null

**Check Configuration:**
```bash
php artisan tinker
>>> config('auth.verification.enabled')
```

**Solution:**
```env
# In .env
AUTH_EMAIL_VERIFICATION_ENABLED=true

# Or disable if not needed
AUTH_EMAIL_VERIFICATION_ENABLED=false
```

---

### Issue: Invitation URL Incorrect

**Symptoms:**
- Invitation link points to wrong domain
- `localhost` in production

**Check:**
```bash
php artisan tinker
>>> env('FRONTEND_URL')
>>> env('APP_URL')
```

**Solution:**
```env
# In .env
FRONTEND_URL=https://app.yourcompany.com
APP_URL=https://api.yourcompany.com
```

---

## Performance Issues

### Issue: Slow Invitation Creation

**Symptoms:**
- Invitation creation takes > 2 seconds
- Timeout errors

**Possible Causes & Solutions:**

#### 1. Email Sending Delay

**Solution:**
- Use queue for email sending
- Configure mail queue in `.env`

```env
QUEUE_CONNECTION=redis
```

```php
// In TenantInvitationService
Mail::to($email)->queue(new TenantInvitationMail($invitation));
```

#### 2. Database Indexing

**Check Indexes:**
```sql
SHOW INDEX FROM tenant_invitations;
```

**Solution:**
- Ensure all indexes exist (token, email, ownership_id, status)
- Run migrations properly

#### 3. SMTP Server Slow

**Solution:**
- Use faster SMTP provider
- Implement email queue
- Use async email sending

---

## Multi-use Invitation Issues

### Issue: Cannot Accept Multi-use Invitation

**Symptoms:**
- Error when multiple tenants try to accept
- Second tenant gets error

**Check:**
```sql
SELECT email, phone, status 
FROM tenant_invitations 
WHERE token = '{token}';
```

**Solution:**
- Ensure both `email` AND `phone` are `NULL`
- If either is set, it's single-use

---

### Issue: Cannot See Tenants Count

**Symptoms:**
- `tenants_count` is null or 0
- Tenants not showing in response

**Solution:**
- Load `tenants` relationship explicitly

```php
// In controller
$invitation->load('tenants.user');
```

```bash
# API request
GET /api/v1/tenants/invitations/{uuid}
```

---

## Development Issues

### Issue: Email Log File Not Created

**Symptoms:**
- `storage/logs/emails.log` doesn't exist
- Emails not logged

**Solution:**
```bash
# Create log file
touch storage/logs/emails.log

# Set permissions
chmod 664 storage/logs/emails.log

# Check logging config
php artisan tinker
>>> config('logging.channels.emails')
```

---

### Issue: Migration Failed

**Symptoms:**
- Error: "Table already exists"
- Migration rollback issues

**Solution:**
```bash
# Rollback specific migration
php artisan migrate:rollback --step=1

# Or fresh migration (WARNING: deletes all data)
php artisan migrate:fresh

# Run specific migration
php artisan migrate --path=database/migrations/2025_12_15_070612_create_tenant_invitations_table.php
```

---

## API Testing Issues

### Issue: Postman Cookie Not Sent

**Symptoms:**
- Ownership scope error
- Cookie not recognized

**Solution:**
1. Enable cookies in Postman settings
2. Set cookie manually in request headers:
```
Cookie: ownership_uuid={uuid}
```

---

### Issue: CORS Error in Frontend

**Symptoms:**
- Error: "CORS policy blocked"
- Frontend cannot call API

**Solution:**
```php
// In config/cors.php
'paths' => ['api/*'],
'allowed_origins' => [env('FRONTEND_URL')],
'supports_credentials' => true,
```

---

## Quick Diagnostics

### Check System Health

```bash
# 1. Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# 2. Check mail configuration
>>> config('mail.default');

# 3. Check frontend URL
>>> env('FRONTEND_URL');

# 4. Test invitation service
>>> $service = app(\App\Services\V1\Tenant\TenantInvitationService::class);
>>> $service->all()->count();

# 5. Check permissions
>>> \Spatie\Permission\Models\Permission::where('name', 'like', 'tenants.invitations.%')->get();
```

---

## Getting Help

### Logs to Check

1. **Laravel Log:** `storage/logs/laravel.log`
2. **Email Log:** `storage/logs/emails.log`
3. **Web Server Log:** `/var/log/nginx/error.log` or Apache equivalent
4. **Database Log:** MySQL/PostgreSQL logs

### Information to Gather

When reporting issues, include:
- Error message (exact text)
- Request payload (if API)
- Token value (for token issues)
- User ID and ownership ID
- Relevant log entries
- Steps to reproduce

---

## Related Documentation

- **[Testing Guide](./11-testing-guide.md)**
- **[API Endpoints - Owner](./03-api-endpoints-owner.md)**
- **[API Endpoints - Public](./04-api-endpoints-public.md)**
- **[Mail Configuration](./08-mail-configuration.md)**

