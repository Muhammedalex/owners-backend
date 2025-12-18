# Testing Guide

## Overview

This guide provides comprehensive testing instructions for the Tenant Invitation feature, including unit tests, integration tests, and manual testing scenarios.

---

## Setup

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Run Seeders

```bash
# Seed all data
php artisan db:seed

# Or seed specific modules
php artisan db:seed --class=Database\\Seeders\\V1\\Auth\\AuthModuleSeeder
php artisan db:seed --class=Database\\Seeders\\V1\\Ownership\\OwnershipModuleSeeder
php artisan db:seed --class=Database\\Seeders\\V1\\Setting\\SystemSettingSeeder
```

### 3. Configure Environment

```env
# Email logging for testing
MAIL_MAILER=log
MAIL_LOG_CHANNEL=emails

# Email verification (optional)
AUTH_EMAIL_VERIFICATION_ENABLED=false

# Frontend URL for invitation links
FRONTEND_URL=http://localhost:3000
```

---

## Artisan Test Command

### Quick Testing Command

**Location:** `app/Console/Commands/TestTenantInvitation.php`

### Usage

#### Test Single Invitation
```bash
php artisan test:tenant-invitation single --email=test@example.com --ownership=1
```

#### Test Bulk Invitations
```bash
php artisan test:tenant-invitation bulk --ownership=1 --count=3
```

#### Test Link-only Invitation (Multi-use)
```bash
php artisan test:tenant-invitation link --ownership=1
```

### Options

| Option | Description | Required |
|--------|-------------|----------|
| `--email` | Email address for invitation | For single |
| `--ownership` | Ownership ID | Yes |
| `--user` | User ID who creates invitation | No (default: 1) |
| `--count` | Number of invitations for bulk | For bulk |

### Output Example

```
✅ Invitation created successfully!

📧 Email: test@example.com
👤 Name: Test User
🔗 Token: abc123...
📅 Expires: 2025-12-22 10:00:00
🌐 URL: http://localhost:3000/register/tenant?token=abc123...

📬 Check email log: storage/logs/emails.log
```

---

## Manual Testing Scenarios

### Scenario 1: Single Invitation Flow

#### 1.1. Create Invitation

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/tenants/invitations \
  -H "Authorization: Bearer {token}" \
  -H "Cookie: ownership_uuid={uuid}" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "tenant@example.com",
    "name": "Ahmed Ali",
    "expires_in_days": 7
  }'
```

**Expected:**
- ✅ Status: 201 Created
- ✅ Invitation created in database
- ✅ Email logged to `storage/logs/emails.log`
- ✅ Token generated

#### 1.2. Check Email Log

```bash
tail -f storage/logs/emails.log
```

**Expected:**
- ✅ Email subject: "You're invited to register as a tenant"
- ✅ Invitation link present
- ✅ Expiration date mentioned

#### 1.3. Validate Token

```bash
curl http://localhost:8000/api/v1/public/tenant-invitations/{token}/validate
```

**Expected:**
- ✅ Status: 200 OK
- ✅ `valid: true`
- ✅ Invitation details returned

#### 1.4. Accept Invitation

```bash
curl -X POST http://localhost:8000/api/v1/public/tenant-invitations/{token}/accept \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Ahmed",
    "last_name": "Ali",
    "email": "tenant@example.com",
    "phone": "+966502234567",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "national_id": "1234567880",
    "id_type": "national_id"
  }'
```

**Expected:**
- ✅ Status: 201 Created
- ✅ User created with type `tenant`
- ✅ Tenant profile created
- ✅ Ownership mapping created
- ✅ Invitation marked as `accepted`
- ✅ Access token returned

#### 1.5. Verify Database

```sql
-- Check user
SELECT * FROM users WHERE email = 'tenant@example.com';
-- Expected: type = 'tenant'

-- Check tenant
SELECT * FROM tenants WHERE user_id = {user_id};
-- Expected: Record exists

-- Check ownership mapping
SELECT * FROM user_ownership_mapping WHERE user_id = {user_id};
-- Expected: Record exists

-- Check invitation
SELECT * FROM tenant_invitations WHERE token = '{token}';
-- Expected: status = 'accepted', accepted_by = {user_id}
```

---

### Scenario 2: Multi-use Invitation Flow

#### 2.1. Generate Link (No Email)

```bash
curl -X POST http://localhost:8000/api/v1/tenants/invitations/generate-link \
  -H "Authorization: Bearer {token}" \
  -H "Cookie: ownership_uuid={uuid}" \
  -H "Content-Type: application/json" \
  -d '{
    "expires_in_days": 30
  }'
```

**Expected:**
- ✅ Status: 201 Created
- ✅ Invitation created with no email/phone
- ✅ No email sent
- ✅ Link generated

#### 2.2. First Tenant Accepts

```bash
curl -X POST http://localhost:8000/api/v1/public/tenant-invitations/{token}/accept \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "First",
    "last_name": "Tenant",
    "email": "tenant1@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "national_id": "1111111111"
  }'
```

**Expected:**
- ✅ Tenant 1 created
- ✅ Invitation status: `pending` (not changed)
- ✅ Token still valid

#### 2.3. Second Tenant Accepts (Same Link)

```bash
curl -X POST http://localhost:8000/api/v1/public/tenant-invitations/{token}/accept \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Second",
    "last_name": "Tenant",
    "email": "tenant2@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "national_id": "2222222222"
  }'
```

**Expected:**
- ✅ Tenant 2 created
- ✅ Invitation status: `pending` (still not changed)
- ✅ Token still valid

#### 2.4. View Invitation (Check Tenants)

```bash
curl http://localhost:8000/api/v1/tenants/invitations/{uuid} \
  -H "Authorization: Bearer {token}" \
  -H "Cookie: ownership_uuid={uuid}"
```

**Expected:**
- ✅ `tenants_count: 2`
- ✅ `tenants` array with both tenants
- ✅ Status: `pending`

#### 2.5. Close Invitation

```bash
curl -X POST http://localhost:8000/api/v1/tenants/invitations/{uuid}/cancel \
  -H "Authorization: Bearer {token}" \
  -H "Cookie: ownership_uuid={uuid}"
```

**Expected:**
- ✅ Status: `cancelled`
- ✅ Token no longer valid

---

### Scenario 3: Bulk Invitations

#### 3.1. Create Bulk Invitations

```bash
curl -X POST http://localhost:8000/api/v1/tenants/invitations/bulk \
  -H "Authorization: Bearer {token}" \
  -H "Cookie: ownership_uuid={uuid}" \
  -H "Content-Type: application/json" \
  -d '{
    "invitations": [
      {"email": "tenant1@example.com", "name": "Tenant One"},
      {"email": "tenant2@example.com", "name": "Tenant Two"},
      {"email": "tenant3@example.com", "name": "Tenant Three"}
    ],
    "expires_in_days": 7
  }'
```

**Expected:**
- ✅ 3 invitations created
- ✅ 3 emails sent
- ✅ All with `pending` status

---

### Scenario 4: Error Cases

#### 4.1. Expired Invitation

**Test:**
1. Create invitation with `expires_in_days: 0`
2. Wait 1 minute
3. Try to accept

**Expected:**
- ❌ Status: 400 Bad Request
- ❌ Message: "Invitation has expired"

#### 4.2. Already Accepted (Single-use)

**Test:**
1. Create single invitation
2. Accept invitation
3. Try to accept again

**Expected:**
- ❌ Status: 400 Bad Request
- ❌ Message: "Invitation has already been accepted"

#### 4.3. Email Mismatch

**Test:**
1. Create invitation with `email: "test@example.com"`
2. Try to accept with different email

**Expected:**
- ❌ Status: 400 Bad Request
- ❌ Message: "Email does not match invitation"

#### 4.4. Tenant Already Exists

**Test:**
1. Accept invitation (creates tenant)
2. Create new invitation for same user/ownership
3. Try to accept

**Expected:**
- ❌ Status: 400 Bad Request
- ❌ Message: "Tenant already exists for this ownership"

---

## Postman Testing

### Import Collection

**Location:** `docs/postman/Tenant_Invitations_API.postman_collection.json`

### Setup

1. Import collection into Postman
2. Set environment variables:
   - `base_url`: `http://localhost:8000`
   - `access_token`: (get from login)
   - `ownership_uuid`: (get from user)

### Run Tests

1. **Login** → Get access token
2. **Create Invitation** → Creates and sends invitation
3. **List Invitations** → View all invitations
4. **Validate Token** → Check token validity
5. **Accept Invitation** → Complete registration
6. **View Invitation** → Check acceptance status

### Automated Tests

Each request includes test scripts:
- Status code validation
- Response structure validation
- Token extraction
- Data persistence checks

---

## Database Testing

### Verify Tables

```sql
-- Check invitation
SELECT * FROM tenant_invitations 
WHERE ownership_id = 1 
ORDER BY created_at DESC LIMIT 10;

-- Check tenants
SELECT t.*, u.email, u.first, u.last
FROM tenants t
JOIN users u ON t.user_id = u.id
WHERE t.ownership_id = 1
ORDER BY t.created_at DESC LIMIT 10;

-- Check ownership mappings
SELECT m.*, u.email, o.name
FROM user_ownership_mapping m
JOIN users u ON m.user_id = u.id
JOIN ownerships o ON m.ownership_id = o.id
WHERE m.ownership_id = 1
ORDER BY m.created_at DESC LIMIT 10;

-- Check multi-use invitations with tenant count
SELECT 
    ti.id,
    ti.token,
    ti.status,
    ti.email,
    ti.phone,
    COUNT(t.id) as tenants_count
FROM tenant_invitations ti
LEFT JOIN tenants t ON t.invitation_id = ti.id
WHERE ti.ownership_id = 1
  AND ti.email IS NULL
  AND ti.phone IS NULL
GROUP BY ti.id, ti.token, ti.status, ti.email, ti.phone;
```

---

## Performance Testing

### Load Test: Create Invitations

```bash
# Create 100 invitations
for i in {1..100}; do
  curl -X POST http://localhost:8000/api/v1/tenants/invitations \
    -H "Authorization: Bearer {token}" \
    -H "Cookie: ownership_uuid={uuid}" \
    -H "Content-Type: application/json" \
    -d "{\"email\": \"tenant$i@example.com\", \"name\": \"Tenant $i\"}" &
done
wait
```

### Load Test: Accept Invitations

```bash
# Accept 100 invitations concurrently
# (requires 100 tokens from previous step)
for token in {token1..token100}; do
  curl -X POST http://localhost:8000/api/v1/public/tenant-invitations/$token/accept \
    -H "Content-Type: application/json" \
    -d "{...}" &
done
wait
```

---

## Troubleshooting Tests

### Check Email Logs

```bash
# Real-time monitoring
tail -f storage/logs/emails.log

# Search for specific email
grep "tenant@example.com" storage/logs/emails.log

# Count emails sent
grep -c "Subject:" storage/logs/emails.log
```

### Check Laravel Logs

```bash
# Real-time monitoring
tail -f storage/logs/laravel.log

# Search for errors
grep "ERROR" storage/logs/laravel.log

# Search for invitation-related logs
grep "invitation" storage/logs/laravel.log
```

---

## Cleanup

### Delete Test Data

```sql
-- Delete test invitations
DELETE FROM tenant_invitations 
WHERE email LIKE 'test%@example.com';

-- Delete test tenants
DELETE FROM tenants 
WHERE national_id LIKE 'TEST%';

-- Delete test users
DELETE FROM users 
WHERE email LIKE 'test%@example.com';
```

### Reset Database

```bash
php artisan migrate:fresh --seed
```

---

## Related Files

- **Test Command:** `app/Console/Commands/TestTenantInvitation.php`
- **Postman Collection:** `docs/postman/Tenant_Invitations_API.postman_collection.json`
- **Email Template:** `resources/views/emails/v1/tenant/invitation.blade.php`

---

## Related Documentation

- **[API Endpoints - Owner](./03-api-endpoints-owner.md)**
- **[API Endpoints - Public](./04-api-endpoints-public.md)**
- **[Troubleshooting](./12-troubleshooting.md)**

