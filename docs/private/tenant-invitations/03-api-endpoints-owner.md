# API Endpoints - Owner (Authenticated)

## Overview

Owner-facing API endpoints for managing tenant invitations. All endpoints require authentication and ownership scope.

**Base URL:** `/api/v1/tenants/invitations`

**Authentication:** Bearer Token (Sanctum)  
**Ownership Scope:** Required (via `ownership_uuid` cookie)

---

## Endpoints

### 1. List Invitations

**GET** `/api/v1/tenants/invitations`

List all invitations for the current ownership.

#### Headers

```
Authorization: Bearer {access_token}
Cookie: ownership_uuid={ownership_uuid}
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `per_page` | integer | No | Items per page (default: 15) |
| `page` | integer | No | Page number |
| `search` | string | No | Search by email, name, or token |
| `status` | string | No | Filter by status: `pending`, `accepted`, `expired`, `cancelled` |
| `pending` | boolean | No | Show only pending invitations |
| `expired` | boolean | No | Show only expired invitations |
| `accepted` | boolean | No | Show only accepted invitations |

#### Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "tenant@example.com",
      "phone": null,
      "name": "Ahmed Ali",
      "status": "pending",
      "expires_at": "2025-12-22 10:00:00",
      "accepted_at": null,
      "notes": null,
      "invitation_url": "https://app.example.com/register/tenant?token=abc123...",
      "is_expired": false,
      "is_pending": true,
      "is_accepted": false,
      "is_cancelled": false,
      "ownership": {
        "uuid": "ownership-uuid",
        "name": "ABC Real Estate"
      },
      "invited_by": {
        "uuid": "user-uuid",
        "name": "John Doe",
        "email": "owner@example.com"
      },
      "accepted_by": null,
      "tenant": null,
      "tenants_count": null,
      "tenants": null,
      "created_at": "2025-12-15 10:00:00",
      "updated_at": "2025-12-15 10:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

#### Permissions Required

- `tenants.invitations.view`

---

### 2. Create Single Invitation

**POST** `/api/v1/tenants/invitations`

Create a single invitation and send email (if email provided).

#### Headers

```
Authorization: Bearer {access_token}
Cookie: ownership_uuid={ownership_uuid}
Content-Type: application/json
```

#### Request Body

```json
{
  "email": "tenant@example.com",
  "phone": null,
  "name": "Ahmed Ali",
  "expires_in_days": 7,
  "notes": "Invitation for new office tenant"
}
```

#### Validation Rules

- `email`: Required if `phone` is not provided, nullable, email format, max 255 chars
- `phone`: Required if `email` is not provided, nullable, Saudi phone format, max 20 chars
- `name`: Optional, string, max 255 chars
- `expires_in_days`: Optional, integer, min 1, max 30 (default: 7)
- `notes`: Optional, string

**Note:** Either `email` OR `phone` must be provided (or both can be null for multi-use invitation).

#### Response (201 Created)

```json
{
  "success": true,
  "message": "Invitation sent successfully",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "email": "tenant@example.com",
    "phone": null,
    "name": "Ahmed Ali",
    "status": "pending",
    "expires_at": "2025-12-22 10:00:00",
    "invitation_url": "https://app.example.com/register/tenant?token=abc123...",
    "is_expired": false,
    "is_pending": true,
    "ownership": {
      "uuid": "ownership-uuid",
      "name": "ABC Real Estate"
    },
    "invited_by": {
      "uuid": "user-uuid",
      "name": "John Doe",
      "email": "owner@example.com"
    }
  }
}
```

#### Permissions Required

- `tenants.invitations.create`

---

### 3. Create Bulk Invitations

**POST** `/api/v1/tenants/invitations/bulk`

Create multiple invitations at once.

#### Headers

```
Authorization: Bearer {access_token}
Cookie: ownership_uuid={ownership_uuid}
Content-Type: application/json
```

#### Request Body

```json
{
  "invitations": [
    {
      "email": "tenant1@example.com",
      "name": "Tenant One"
    },
    {
      "email": "tenant2@example.com",
      "name": "Tenant Two"
    },
    {
      "phone": "+966501234567",
      "name": "Tenant Three"
    }
  ],
  "expires_in_days": 7,
  "notes": "Bulk invitation for new building"
}
```

#### Validation Rules

- `invitations`: Required, array, min 1, max 100 items
- `invitations.*.email`: Required if `phone` not provided, nullable, email format
- `invitations.*.phone`: Required if `email` not provided, nullable, Saudi phone format
- `invitations.*.name`: Optional, string, max 255 chars
- `expires_in_days`: Optional, integer, min 1, max 30 (default: 7)
- `notes`: Optional, string

#### Response (201 Created)

```json
{
  "success": true,
  "message": "Invitations sent successfully",
  "data": [
    {
      "uuid": "uuid-1",
      "email": "tenant1@example.com",
      "status": "pending",
      ...
    },
    {
      "uuid": "uuid-2",
      "email": "tenant2@example.com",
      "status": "pending",
      ...
    }
  ]
}
```

#### Permissions Required

- `tenants.invitations.create`

---

### 4. Generate Link (No Email)

**POST** `/api/v1/tenants/invitations/generate-link`

Generate an invitation link without sending email. Useful for multi-use invitations or manual sharing.

#### Headers

```
Authorization: Bearer {access_token}
Cookie: ownership_uuid={ownership_uuid}
Content-Type: application/json
```

#### Request Body

```json
{
  "email": null,
  "phone": null,
  "name": null,
  "expires_in_days": 30,
  "notes": "Public invitation link for website"
}
```

#### Validation Rules

- `email`: Optional, email format, max 255 chars
- `phone`: Optional, Saudi phone format, max 20 chars
- `name`: Optional, string, max 255 chars
- `expires_in_days`: Optional, integer, min 1, max 30 (default: 7)
- `notes`: Optional, string

**Note:** If both `email` and `phone` are null, creates a multi-use invitation.

#### Response (201 Created)

```json
{
  "success": true,
  "message": "Invitation link generated successfully",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "email": null,
    "phone": null,
    "name": null,
    "status": "pending",
    "expires_at": "2026-01-15 10:00:00",
    "invitation_url": "https://app.example.com/register/tenant?token=xyz789...",
    "is_expired": false,
    "is_pending": true
  }
}
```

#### Permissions Required

- `tenants.invitations.create`

---

### 5. Show Invitation

**GET** `/api/v1/tenants/invitations/{uuid}`

Get details of a specific invitation.

#### Headers

```
Authorization: Bearer {access_token}
Cookie: ownership_uuid={ownership_uuid}
```

#### URL Parameters

- `uuid`: Invitation UUID

#### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "email": "tenant@example.com",
    "status": "accepted",
    "expires_at": "2025-12-22 10:00:00",
    "accepted_at": "2025-12-15 11:00:00",
    "invitation_url": "https://app.example.com/register/tenant?token=abc123...",
    "ownership": {
      "uuid": "ownership-uuid",
      "name": "ABC Real Estate"
    },
    "invited_by": {
      "uuid": "user-uuid",
      "name": "John Doe",
      "email": "owner@example.com"
    },
    "accepted_by": {
      "uuid": "tenant-user-uuid",
      "name": "Ahmed Ali",
      "email": "tenant@example.com"
    },
    "tenant": {
      "id": 1,
      "national_id": "1234567890"
    },
    "tenants_count": null,
    "tenants": null,
    "created_at": "2025-12-15 10:00:00",
    "updated_at": "2025-12-15 11:00:00"
  }
}
```

**For Multi-use Invitations:**

```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "email": null,
    "phone": null,
    "status": "pending",
    "tenants_count": 3,
    "tenants": [
      {
        "id": 1,
        "user": {
          "name": "Ahmed Ali",
          "email": "ahmed@example.com"
        },
        "national_id": "1234567890",
        "created_at": "2025-12-15 11:00:00"
      },
      {
        "id": 2,
        "user": {
          "name": "Mohammed Ali",
          "email": "mohammed@example.com"
        },
        "national_id": "0987654321",
        "created_at": "2025-12-15 12:00:00"
      }
    ]
  }
}
```

#### Permissions Required

- `tenants.invitations.view`

---

### 6. Resend Invitation

**POST** `/api/v1/tenants/invitations/{uuid}/resend`

Resend invitation email (only if invitation has email).

#### Headers

```
Authorization: Bearer {access_token}
Cookie: ownership_uuid={ownership_uuid}
```

#### URL Parameters

- `uuid`: Invitation UUID

#### Response (200 OK)

```json
{
  "success": true,
  "message": "Invitation resent successfully",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "email": "tenant@example.com",
    "status": "pending",
    ...
  }
}
```

#### Error Response (400 Bad Request)

```json
{
  "success": false,
  "message": "Failed to resend invitation",
  "error": "Invitation does not have an email address"
}
```

#### Permissions Required

- `tenants.invitations.resend`

---

### 7. Cancel Invitation

**POST** `/api/v1/tenants/invitations/{uuid}/cancel`

Cancel a pending invitation.

#### Headers

```
Authorization: Bearer {access_token}
Cookie: ownership_uuid={ownership_uuid}
```

#### URL Parameters

- `uuid`: Invitation UUID

#### Response (200 OK)

```json
{
  "success": true,
  "message": "Invitation cancelled successfully",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "cancelled",
    ...
  }
}
```

#### Permissions Required

- **For invitations with email/phone:** `tenants.invitations.cancel`
- **For invitations without email/phone:** `tenants.invitations.close_without_contact`

---

## Error Responses

### 400 Bad Request

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required when phone is not present."]
  }
}
```

### 401 Unauthorized

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 403 Forbidden

```json
{
  "success": false,
  "message": "This action is unauthorized"
}
```

### 404 Not Found

```json
{
  "success": false,
  "message": "Invitation not found"
}
```

---

## Request Examples

### cURL Examples

#### Create Invitation

```bash
curl -X POST https://api.example.com/api/v1/tenants/invitations \
  -H "Authorization: Bearer {token}" \
  -H "Cookie: ownership_uuid={uuid}" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "tenant@example.com",
    "name": "Ahmed Ali",
    "expires_in_days": 7
  }'
```

#### List Invitations

```bash
curl -X GET "https://api.example.com/api/v1/tenants/invitations?status=pending&per_page=20" \
  -H "Authorization: Bearer {token}" \
  -H "Cookie: ownership_uuid={uuid}"
```

#### Resend Invitation

```bash
curl -X POST https://api.example.com/api/v1/tenants/invitations/{uuid}/resend \
  -H "Authorization: Bearer {token}" \
  -H "Cookie: ownership_uuid={uuid}"
```

---

## Related Files

- **Controller:** `app/Http/Controllers/Api/V1/Tenant/TenantInvitationController.php`
- **Request (Single):** `app/Http/Requests/V1/Tenant/StoreTenantInvitationRequest.php`
- **Request (Bulk):** `app/Http/Requests/V1/Tenant/StoreBulkTenantInvitationRequest.php`
- **Resource:** `app/Http/Resources/V1/Tenant/TenantInvitationResource.php`
- **Policy:** `app/Policies/V1/Tenant/TenantInvitationPolicy.php`

