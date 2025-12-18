# Tenant Invitations API - Postman Collection

## Quick Start

### Import Collection

1. Open Postman
2. Click **Import** button
3. Select `Tenant_Invitations_API.postman_collection.json`
4. Collection will be imported with all endpoints

### Setup Environment

1. Create a new environment in Postman (or use existing)
2. Add these variables:

| Variable | Initial Value | Current Value | Description |
|----------|---------------|---------------|-------------|
| `base_url` | `http://localhost:8000` | `http://localhost:8000` | API base URL |
| `access_token` | (empty) | (auto-set) | Bearer token for auth |
| `invitation_uuid` | (empty) | (auto-set) | Invitation UUID |
| `invitation_token` | (empty) | (auto-set) | Invitation token |

3. Select the environment before making requests

### Auto Token Management

The collection includes scripts that automatically:
- ✅ Save `access_token` after login
- ✅ Save `invitation_uuid` after creating invitation
- ✅ Save `invitation_token` after creating invitation
- ✅ Use tokens in Authorization headers
- ✅ Use variables in request URLs

**No manual token management needed!**

---

## Collection Structure

```
Tenant Invitations API - V1
├── Authentication
│   └── Login
├── Owner Endpoints
│   ├── List Invitations
│   ├── Create Single Invitation
│   ├── Create Bulk Invitations
│   ├── Generate Link (No Email)
│   ├── Show Invitation
│   ├── Resend Invitation
│   └── Cancel Invitation
├── Public Endpoints
│   ├── Validate Token
│   └── Accept Invitation
└── Test Endpoints (Development Only)
    ├── Test Create Invitation
    ├── Test Bulk Invitations
    └── Test Generate Link
```

---

## Usage Flow

### 1. Login as Owner

1. Open **Authentication → Login** request
2. Update email/phone and password
3. Send request
4. `access_token` is automatically saved
5. `ownership_uuid` cookie is automatically set

### 2. Create Invitation

**Option A: Single Invitation (Email)**
1. Open **Owner Endpoints → Create Single Invitation**
2. Update request body with tenant email
3. Send request
4. Email is sent automatically
5. `invitation_uuid` and `invitation_token` are saved

**Option B: Bulk Invitations**
1. Open **Owner Endpoints → Create Bulk Invitations**
2. Update request body with array of invitations
3. Send request
4. Emails are sent to all recipients

**Option C: Generate Link (No Email)**
1. Open **Owner Endpoints → Generate Link**
2. Update request body with tenant info
3. Send request
4. Copy invitation URL and share manually

### 3. Tenant Registration Flow

1. Tenant receives email with registration link
2. Tenant clicks link → Frontend validates token
3. Frontend calls **Public Endpoints → Validate Token**
4. Frontend shows registration form
5. Tenant submits form → Frontend calls **Public Endpoints → Accept Invitation**
6. System creates user account + tenant profile
7. Tenant receives access token for immediate login

---

## Endpoint Details

### Owner Endpoints (Authenticated)

All owner endpoints require:
- ✅ Bearer token in Authorization header
- ✅ Ownership scope cookie (set automatically after login)

#### List Invitations
- **Method:** GET
- **URL:** `/api/v1/tenants/invitations`
- **Query Params:** `page`, `per_page`, `status`, `search`
- **Response:** Paginated list of invitations

#### Create Single Invitation
- **Method:** POST
- **URL:** `/api/v1/tenants/invitations`
- **Body:** `email`, `name`, `phone`, `expires_in_days`, `notes`
- **Action:** Creates invitation + sends email
- **Response:** Invitation details with token

#### Create Bulk Invitations
- **Method:** POST
- **URL:** `/api/v1/tenants/invitations/bulk`
- **Body:** `invitations[]`, `expires_in_days`, `notes`
- **Action:** Creates multiple invitations + sends emails
- **Response:** Array of created invitations
- **Limit:** Max 100 invitations per request

#### Generate Link (No Email)
- **Method:** POST
- **URL:** `/api/v1/tenants/invitations/generate-link`
- **Body:** `email`, `name`, `phone`, `expires_in_days`
- **Action:** Creates invitation without sending email
- **Response:** Invitation URL for manual sharing

#### Show Invitation
- **Method:** GET
- **URL:** `/api/v1/tenants/invitations/{uuid}`
- **Response:** Full invitation details

#### Resend Invitation
- **Method:** POST
- **URL:** `/api/v1/tenants/invitations/{uuid}/resend`
- **Action:** Sends invitation email again
- **Limitation:** Only for pending invitations

#### Cancel Invitation
- **Method:** POST
- **URL:** `/api/v1/tenants/invitations/{uuid}/cancel`
- **Action:** Cancels pending invitation
- **Limitation:** Only for pending invitations

### Public Endpoints (No Authentication)

#### Validate Token
- **Method:** GET
- **URL:** `/api/v1/public/tenant-invitations/{token}/validate`
- **Purpose:** Check if token is valid before showing registration form
- **Response:** Invitation details if valid
- **Errors:** 404 (not found), 400 (expired/accepted/cancelled)

#### Accept Invitation
- **Method:** POST
- **URL:** `/api/v1/public/tenant-invitations/{token}/accept`
- **Body:** Registration form data (first_name, last_name, email, password, etc.)
- **Action:** Creates user account + tenant profile
- **Response:** User details + access token
- **Errors:** 404, 400, 422 (validation)

### Test Endpoints (Development Only)

Available only in non-production environments:
- **Test Create Invitation:** `/api/v1/test/tenant-invitations/create`
- **Test Bulk Invitations:** `/api/v1/test/tenant-invitations/bulk`
- **Test Generate Link:** `/api/v1/test/tenant-invitations/generate-link`

These endpoints don't require authentication and use first available ownership/user.

---

## Request Examples

### Create Single Invitation

```json
POST /api/v1/tenants/invitations
Authorization: Bearer {token}

{
  "email": "tenant@example.com",
  "name": "Ahmed Ali",
  "phone": "+966501234567",
  "expires_in_days": 7,
  "notes": "Invitation for new office tenant"
}
```

### Create Bulk Invitations

```json
POST /api/v1/tenants/invitations/bulk
Authorization: Bearer {token}

{
  "invitations": [
    {
      "email": "tenant1@example.com",
      "name": "Tenant One"
    },
    {
      "email": "tenant2@example.com",
      "name": "Tenant Two",
      "phone": "+966501234568"
    }
  ],
  "expires_in_days": 7,
  "notes": "Bulk invitation for new building"
}
```

### Accept Invitation

```json
POST /api/v1/public/tenant-invitations/{token}/accept

{
  "first_name": "Ahmed",
  "last_name": "Ali",
  "email": "tenant@example.com",
  "phone": "+966501234567",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!",
  "national_id": "1234567890",
  "id_type": "national_id",
  "id_expiry": "2030-12-31",
  "emergency_name": "Mohammed Ali",
  "emergency_phone": "+966507654321",
  "emergency_relation": "brother",
  "employment": "employed",
  "employer": "ABC Company",
  "income": 15000.00
}
```

---

## Response Examples

### Success - Invitation Created

```json
{
  "success": true,
  "message": "Tenant invitation sent successfully.",
  "data": {
    "invitation": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "tenant@example.com",
      "phone": "+966501234567",
      "name": "Ahmed Ali",
      "token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
      "status": "pending",
      "expires_at": "2025-12-22T10:00:00.000000Z",
      "created_at": "2025-12-15T10:00:00.000000Z"
    }
  }
}
```

### Success - Registration Complete

```json
{
  "success": true,
  "message": "Tenant invitation accepted successfully. Your account is ready.",
  "data": {
    "user": {
      "id": 10,
      "uuid": "770e8400-e29b-41d4-a716-446655440000",
      "email": "tenant@example.com",
      "name": "Ahmed Ali"
    },
    "tenant": {
      "id": 5,
      "user_id": 10,
      "ownership_id": 1,
      "national_id": "1234567890"
    },
    "tokens": {
      "access_token": "10|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  }
}
```

### Error - Expired Token

```json
{
  "success": false,
  "message": "This invitation has expired.",
  "errors": {}
}
```

---

## Testing Workflow

### Complete Invitation Flow

1. **Login** → Get access token
2. **Create Single Invitation** → Send invitation email
3. **List Invitations** → View created invitations
4. **Show Invitation** → View invitation details
5. **Validate Token** (Public) → Check token validity
6. **Accept Invitation** (Public) → Complete registration
7. **List Invitations** → Verify status changed to "accepted"

### Bulk Invitation Flow

1. **Login** → Get access token
2. **Create Bulk Invitations** → Send multiple emails
3. **List Invitations** → View all created invitations
4. Test individual invitation acceptance

### Link Generation Flow

1. **Login** → Get access token
2. **Generate Link** → Get invitation URL
3. Copy URL and share manually (SMS, WhatsApp, etc.)
4. Tenant uses URL to register

---

## Features

### ✅ Complete Documentation
- Detailed request descriptions
- Response examples
- Error scenarios
- Field explanations
- Use cases

### ✅ Auto Token Management
- Automatic token saving after login
- Automatic invitation UUID/token saving
- Automatic token usage in headers
- Environment variable management

### ✅ Test Scripts
- Status code validation
- Response structure validation
- Automatic variable saving
- Error handling

### ✅ Multiple Scenarios
- Single invitation
- Bulk invitations
- Link generation
- Token validation
- Registration acceptance

---

## Email Logging (Development)

In development mode:
- ✅ All emails are logged to `storage/logs/emails.log`
- ✅ No real emails are sent
- ✅ Check log file to view email content
- ✅ Email includes invitation URL with token

**View Email Log:**
```powershell
Get-Content storage\logs\emails.log -Tail 50
```

---

## Invitation Statuses

- **pending**: Invitation sent, waiting for acceptance
- **accepted**: Tenant completed registration
- **cancelled**: Owner cancelled the invitation
- **expired**: Invitation expired (default: 7 days)

---

## Security Features

- ✅ Secure token generation (64 characters)
- ✅ Token expiration (configurable, default: 7 days)
- ✅ Email validation (must match invitation)
- ✅ Ownership scope enforcement
- ✅ Rate limiting on public endpoints
- ✅ Validation on all inputs

---

## Troubleshooting

### 401 Unauthorized

- Verify token is saved in environment
- Check token hasn't expired
- Ensure ownership scope cookie is set
- Try logging in again

### 404 Not Found (Invitation)

- Verify invitation UUID/token is correct
- Check invitation exists in database
- Verify ownership scope matches

### 400 Bad Request (Token)

- Token expired → Create new invitation
- Token already accepted → Cannot reuse
- Token cancelled → Create new invitation

### 422 Validation Error

- Check request body format
- Verify required fields are present
- Review error messages for details
- Check email/phone format

### Email Not Received

- Check `storage/logs/emails.log` in development
- Verify email configuration in production
- Check spam folder
- Use "Resend Invitation" endpoint

---

## Tips

1. **Use Environment Variables** - Set `base_url` for easy switching
2. **Check Auto-Saved Tokens** - Tokens are automatically saved
3. **Test Error Scenarios** - Collection includes error examples
4. **Monitor Invitation Status** - Use List/Show endpoints
5. **Use Bulk for Multiple** - More efficient than single requests
6. **Generate Link for Manual Sharing** - When email isn't preferred

---

## Support

For API issues:
1. Check error response messages
2. Review validation errors
3. Verify token status
4. Check invitation status
5. Review email logs (development)

---

## Related Documentation

- **[Tenant Self-Registration Feature](../../new-features/01-tenant-self-registration.md)** - Complete feature documentation
- **[Testing Guide](../../new-features/TESTING_GUIDE.md)** - Testing instructions
- **[Quick Test Guide](../../new-features/QUICK_TEST.md)** - Quick reference

