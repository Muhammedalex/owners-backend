# API Endpoints - Public (No Authentication)

## Overview

Public API endpoints for tenants to validate and accept invitations. These endpoints do not require authentication.

**Base URL:** `/api/v1/public/tenant-invitations`

**Authentication:** None required

---

## Endpoints

### 1. Validate Invitation Token

**GET** `/api/v1/public/tenant-invitations/{token}/validate`

Validate an invitation token and return invitation details. Used by frontend to check if token is valid before showing registration form.

#### URL Parameters

- `token`: Invitation token (64 characters)

#### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "valid": true,
    "invitation": {
      "email": "tenant@example.com",
      "name": "Ahmed Ali",
      "ownership": {
        "name": "ABC Real Estate"
      },
      "expires_at": "2025-12-22 10:00:00"
    }
  }
}
```

#### Error Responses

**404 Not Found - Invalid Token**

```json
{
  "success": false,
  "message": "Invalid invitation token"
}
```

**400 Bad Request - Expired**

```json
{
  "success": false,
  "message": "Invitation has expired"
}
```

**400 Bad Request - Already Accepted (Single-use)**

```json
{
  "success": false,
  "message": "Invitation has already been accepted"
}
```

**400 Bad Request - Cancelled**

```json
{
  "success": false,
  "message": "Invitation has been cancelled"
}
```

---

### 2. Accept Invitation & Register

**POST** `/api/v1/public/tenant-invitations/{token}/accept`

Accept an invitation and complete tenant registration. Creates user account, tenant profile, assigns role, and links to ownership.

#### URL Parameters

- `token`: Invitation token (64 characters)

#### Request Body

```json
{
  "first_name": "Ahmed",
  "last_name": "Ali",
  "email": "tenant@example.com",
  "phone": "+966502234567",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!",
  "national_id": "1234567880",
  "id_type": "national_id",
  "id_expiry": "2030-12-31",
  "emergency_name": "Mohammed Ali",
  "emergency_phone": "+966507654321",
  "emergency_relation": "brother",
  "employment": "employed",
  "employer": "ABC Company",
  "income": 15000.00,
  "notes": "New tenant registration"
}
```

#### Validation Rules

| Field | Rules | Description |
|-------|-------|-------------|
| `first_name` | Required, string, max 100 | First name |
| `last_name` | Required, string, max 100 | Last name |
| `email` | Required, email, max 255 | Email address (must match invitation email for single-use) |
| `phone` | Optional, Saudi phone format, max 20 | Phone number |
| `password` | Required, string, min 8, confirmed | Password |
| `password_confirmation` | Required, string | Password confirmation |
| `national_id` | Optional, string, max 50 | National ID number |
| `id_type` | Optional, enum: `national_id`, `iqama`, `passport`, `commercial_registration` | ID type |
| `id_expiry` | Optional, date | ID expiration date |
| `emergency_name` | Optional, string, max 100 | Emergency contact name |
| `emergency_phone` | Optional, Saudi phone format, max 20 | Emergency contact phone |
| `emergency_relation` | Optional, string, max 50 | Relationship to emergency contact |
| `employment` | Optional, enum: `employed`, `self_employed`, `unemployed`, `retired`, `student` | Employment status |
| `employer` | Optional, string, max 255 | Employer name |
| `income` | Optional, numeric, min 0, max 9999999999.99 | Monthly income |
| `notes` | Optional, string | Additional notes |

#### Response (201 Created)

```json
{
  "success": true,
  "message": "Registration completed successfully",
  "data": {
    "user": {
      "uuid": "user-uuid",
      "email": "tenant@example.com",
      "first": "Ahmed",
      "last": "Ali",
      "type": "tenant",
      "email_verified_at": null
    },
    "tenant": {
      "id": 1,
      "national_id": "1234567880",
      "id_type": "national_id",
      "ownership": {
        "uuid": "ownership-uuid",
        "name": "ABC Real Estate"
      },
      "user": {
        "uuid": "user-uuid",
        "email": "tenant@example.com"
      }
    },
    "invitation": {
      "uuid": "invitation-uuid",
      "status": "accepted"
    },
    "access_token": "sanctum-access-token",
    "redirect_to": "/dashboard"
  }
}
```

#### What Happens During Acceptance

1. **Token Validation**
   - Validates token exists
   - Checks expiration
   - Checks status (not cancelled, not already accepted for single-use)

2. **Email Validation** (Single-use only)
   - If invitation has email, registration email must match

3. **User Account**
   - Creates new user OR uses existing user
   - Sets `type` to `'tenant'`
   - Assigns `'Tenant'` role
   - Handles email verification (if enabled)

4. **Tenant Profile**
   - Creates tenant record
   - Links to ownership
   - Links to invitation (`invitation_id`)

5. **Ownership Mapping**
   - Creates `UserOwnershipMapping` record
   - Sets as default if first ownership

6. **Invitation Update**
   - **Single-use:** Marks as `accepted`, sets `accepted_by`, sets `tenant_id`
   - **Multi-use:** Updates `updated_at` timestamp, keeps `pending` status

7. **Tokens**
   - Generates Sanctum access token
   - Returns token for immediate login

#### Error Responses

**400 Bad Request - Validation Error**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**400 Bad Request - Email Mismatch**

```json
{
  "success": false,
  "message": "Email does not match invitation."
}
```

**400 Bad Request - Tenant Already Exists**

```json
{
  "success": false,
  "message": "Tenant already exists for this ownership."
}
```

**400 Bad Request - Invalid Token**

```json
{
  "success": false,
  "message": "Invalid invitation token."
}
```

**400 Bad Request - Expired**

```json
{
  "success": false,
  "message": "Invitation has expired."
}
```

**400 Bad Request - Already Accepted**

```json
{
  "success": false,
  "message": "Invitation has already been accepted."
}
```

**400 Bad Request - Cancelled**

```json
{
  "success": false,
  "message": "Invitation has been cancelled."
}
```

---

## Request Examples

### cURL Examples

#### Validate Token

```bash
curl -X GET "https://api.example.com/api/v1/public/tenant-invitations/abc123.../validate"
```

#### Accept Invitation

```bash
curl -X POST "https://api.example.com/api/v1/public/tenant-invitations/abc123.../accept" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Ahmed",
    "last_name": "Ali",
    "email": "tenant@example.com",
    "phone": "+966502234567",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "national_id": "1234567880",
    "id_type": "national_id",
    "id_expiry": "2030-12-31",
    "emergency_name": "Mohammed Ali",
    "emergency_phone": "+966507654321",
    "emergency_relation": "brother",
    "employment": "employed",
    "employer": "ABC Company",
    "income": 15000.00
  }'
```

---

## Frontend Integration

### Step 1: Validate Token on Page Load

```javascript
// Get token from URL
const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');

// Validate token
fetch(`/api/v1/public/tenant-invitations/${token}/validate`)
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show registration form
      // Pre-fill email/name if available
      prefillForm(data.data.invitation);
    } else {
      // Show error message
      showError(data.message);
    }
  });
```

### Step 2: Submit Registration

```javascript
// Submit registration form
fetch(`/api/v1/public/tenant-invitations/${token}/accept`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify(formData)
})
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Save access token
      localStorage.setItem('access_token', data.data.access_token);
      
      // Redirect to dashboard
      window.location.href = data.data.redirect_to;
    } else {
      // Show validation errors
      showErrors(data.errors);
    }
  });
```

---

## Related Files

- **Controller:** `app/Http/Controllers/Api/V1/Tenant/PublicTenantInvitationController.php`
- **Request:** `app/Http/Requests/V1/Tenant/AcceptTenantInvitationRequest.php`
- **Service:** `app/Services/V1/Tenant/TenantInvitationService.php`

