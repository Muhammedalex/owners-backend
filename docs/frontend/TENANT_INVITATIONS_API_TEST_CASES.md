# Tenant Invitations API - Test Cases & Examples

## نظرة عامة

هذا المستند يحتوي على جميع حالات الاختبار والأمثلة الكاملة لـ API الخاص بنظام دعوات المستأجرين. يمكن استخدامه كمرجع للمطورين في Frontend لاختبار جميع السيناريوهات.

---

## جدول المحتويات

1. [Authentication](#authentication)
2. [Owner Endpoints - Test Cases](#owner-endpoints)
3. [Public Endpoints - Test Cases](#public-endpoints)
4. [Error Scenarios](#error-scenarios)
5. [Edge Cases](#edge-cases)
6. [Complete Flow Examples](#complete-flow-examples)

---

## Authentication

### Login

**Endpoint:** `POST /api/v1/auth/login`

#### Test Case 1: Login with Email
```json
{
    "email": "owner@example.com",
    "password": "SecurePassword123!",
    "device_name": "Postman"
}
```

**Expected Response:** `200 OK`
```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "user": {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "owner@example.com",
            "name": "John Doe"
        },
        "tokens": {
            "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "token_type": "Bearer",
            "expires_in": 3600
        }
    }
}
```

**Notes:**
- `refresh_token` يتم حفظه تلقائياً في httpOnly cookie
- `ownership_uuid` يتم حفظه في cookie إذا كان للمستخدم ownership افتراضي

#### Test Case 2: Login with Phone
```json
{
    "phone": "+966501234567",
    "password": "SecurePassword123!"
}
```

**Expected Response:** `200 OK` (same structure as above)

#### Test Case 3: Invalid Credentials
```json
{
    "email": "owner@example.com",
    "password": "WrongPassword"
}
```

**Expected Response:** `401 Unauthorized`
```json
{
    "success": false,
    "message": "Invalid credentials."
}
```

---

## Owner Endpoints

### 1. List Invitations

**Endpoint:** `GET /api/v1/tenants/invitations`

#### Test Case 1: Basic List
**Query Parameters:** None (uses defaults)

**Expected Response:** `200 OK`
```json
{
    "success": true,
    "data": [
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "tenant@example.com",
            "name": "Ahmed Ali",
            "status": "pending",
            "expires_at": "2025-12-22T10:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 50
    }
}
```

#### Test Case 2: Filter by Status
**Query Parameters:** `?status=pending`

**Expected Response:** Only pending invitations

#### Test Case 3: Search by Email
**Query Parameters:** `?search=tenant@example.com`

**Expected Response:** Invitations matching the email

#### Test Case 4: Pagination
**Query Parameters:** `?page=2&per_page=10`

**Expected Response:** Second page with 10 items per page

---

### 2. Create Single Invitation

**Endpoint:** `POST /api/v1/tenants/invitations`

#### Test Case 1: Create with Email Only
```json
{
    "email": "tenant@example.com",
    "name": "Ahmed Ali",
    "expires_in_days": 7
}
```

**Expected Response:** `201 Created`
```json
{
    "success": true,
    "message": "Tenant invitation sent successfully.",
    "data": {
        "invitation": {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "tenant@example.com",
            "name": "Ahmed Ali",
            "token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "status": "pending",
            "expires_at": "2025-12-22T10:00:00.000000Z"
        }
    }
}
```

**What Happens:**
- ✅ Creates invitation record
- ✅ Generates secure token
- ✅ Sends email automatically
- ✅ Email logged to `storage/logs/emails.log` (development)

#### Test Case 2: Create with Phone Only
```json
{
    "phone": "+966501234567",
    "name": "Ahmed Ali"
}
```

**Expected Response:** `201 Created` (same structure)

**Note:** Email not sent (no email provided), but invitation created

#### Test Case 3: Create with Both Email and Phone
```json
{
    "email": "tenant@example.com",
    "phone": "+966501234567",
    "name": "Ahmed Ali"
}
```

**Expected Response:** `201 Created`
**What Happens:** Email sent, phone stored for future SMS

#### Test Case 4: Create Multi-Use Invitation (No Email/Phone)
```json
{
    "name": "General Invitation",
    "expires_in_days": 30,
    "notes": "For website sharing"
}
```

**Expected Response:** `201 Created`
**What Happens:** 
- ✅ Invitation created
- ✅ Link generated
- ✅ No email sent
- ✅ Can be used by multiple tenants

#### Test Case 5: Validation Error - Missing Email/Phone
```json
{
    "name": "Ahmed Ali"
}
```

**Expected Response:** `422 Unprocessable Entity`
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required when phone is not present."],
        "phone": ["The phone field is required when email is not present."]
    }
}
```

#### Test Case 6: Validation Error - Invalid Email
```json
{
    "email": "invalid-email",
    "name": "Ahmed Ali"
}
```

**Expected Response:** `422 Unprocessable Entity`
```json
{
    "errors": {
        "email": ["The email must be a valid email address."]
    }
}
```

#### Test Case 7: Validation Error - Invalid Expiration Days
```json
{
    "email": "tenant@example.com",
    "expires_in_days": 50
}
```

**Expected Response:** `422 Unprocessable Entity`
```json
{
    "errors": {
        "expires_in_days": ["The expires in days may not be greater than 30."]
    }
}
```

---

### 3. Create Bulk Invitations

**Endpoint:** `POST /api/v1/tenants/invitations/bulk`

#### Test Case 1: Bulk Create (3 Invitations)
```json
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
        },
        {
            "email": "tenant3@example.com",
            "name": "Tenant Three",
            "notes": "VIP tenant"
        }
    ],
    "expires_in_days": 7,
    "notes": "Bulk invitation for new building"
}
```

**Expected Response:** `201 Created`
```json
{
    "success": true,
    "message": "Tenant invitations sent successfully.",
    "data": {
        "invitations": [
            {
                "uuid": "550e8400-e29b-41d4-a716-446655440001",
                "email": "tenant1@example.com",
                "name": "Tenant One",
                "status": "pending"
            },
            {
                "uuid": "550e8400-e29b-41d4-a716-446655440002",
                "email": "tenant2@example.com",
                "name": "Tenant Two",
                "status": "pending"
            },
            {
                "uuid": "550e8400-e29b-41d4-a716-446655440003",
                "email": "tenant3@example.com",
                "name": "Tenant Three",
                "status": "pending"
            }
        ]
    }
}
```

**What Happens:**
- ✅ All invitations created
- ✅ Emails sent to all recipients
- ✅ Each has unique token

#### Test Case 2: Bulk Create - Duplicate Emails
```json
{
    "invitations": [
        {"email": "tenant@example.com", "name": "One"},
        {"email": "tenant@example.com", "name": "Two"}
    ]
}
```

**Expected Response:** `422 Unprocessable Entity`
```json
{
    "errors": {
        "invitations.1.email": ["Duplicate email in bulk request."]
    }
}
```

#### Test Case 3: Bulk Create - Too Many (101 Invitations)
```json
{
    "invitations": [
        // ... 101 invitation objects
    ]
}
```

**Expected Response:** `422 Unprocessable Entity`
```json
{
    "errors": {
        "invitations": ["Maximum 100 invitations per request."]
    }
}
```

---

### 4. Generate Link (No Email)

**Endpoint:** `POST /api/v1/tenants/invitations/generate-link`

#### Test Case 1: Generate Link for Multi-Use Invitation
```json
{
    "name": "Website Invitation",
    "expires_in_days": 30
}
```

**Expected Response:** `201 Created`
```json
{
    "success": true,
    "message": "Invitation link generated successfully.",
    "data": {
        "invitation": {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "status": "pending",
            "invitation_url": "http://localhost:3000/register/tenant?token=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "expires_at": "2026-01-15T10:00:00.000000Z"
        }
    }
}
```

**Use Cases:**
- Share on website
- Print QR code
- Share via WhatsApp/SMS
- Share in person

---

### 5. Show Invitation

**Endpoint:** `GET /api/v1/tenants/invitations/{uuid}`

#### Test Case 1: Show Single-Use Invitation
**Path:** `/api/v1/tenants/invitations/550e8400-e29b-41d4-a716-446655440000`

**Expected Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "invitation": {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "tenant@example.com",
            "name": "Ahmed Ali",
            "status": "pending",
            "expires_at": "2025-12-22T10:00:00.000000Z",
            "ownership": {
                "name": "ABC Real Estate"
            },
            "invited_by": {
                "name": "John Doe"
            },
            "tenant": null
        }
    }
}
```

#### Test Case 2: Show Multi-Use Invitation (with Tenants)
**Path:** `/api/v1/tenants/invitations/550e8400-e29b-41d4-a716-446655440000`

**Expected Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "invitation": {
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
                    }
                },
                {
                    "id": 2,
                    "user": {
                        "name": "Mohammed Ali",
                        "email": "mohammed@example.com"
                    }
                }
            ]
        }
    }
}
```

#### Test Case 3: Invitation Not Found
**Path:** `/api/v1/tenants/invitations/invalid-uuid`

**Expected Response:** `404 Not Found`
```json
{
    "success": false,
    "message": "Invitation not found."
}
```

---

### 6. Resend Invitation

**Endpoint:** `POST /api/v1/tenants/invitations/{uuid}/resend`

#### Test Case 1: Resend Pending Invitation
**Path:** `/api/v1/tenants/invitations/550e8400-e29b-41d4-a716-446655440000/resend`

**Expected Response:** `200 OK`
```json
{
    "success": true,
    "message": "Invitation resent successfully."
}
```

**What Happens:**
- ✅ Email sent again
- ✅ Timestamp updated

#### Test Case 2: Resend Already Accepted Invitation
**Path:** `/api/v1/tenants/invitations/accepted-uuid/resend`

**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "Cannot resend already accepted invitation."
}
```

#### Test Case 3: Resend Cancelled Invitation
**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "Cannot resend cancelled invitation."
}
```

#### Test Case 4: Resend Invitation Without Email
**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "Cannot resend invitation without email."
}
```

---

### 7. Cancel Invitation

**Endpoint:** `POST /api/v1/tenants/invitations/{uuid}/cancel`

#### Test Case 1: Cancel Pending Invitation
**Path:** `/api/v1/tenants/invitations/550e8400-e29b-41d4-a716-446655440000/cancel`

**Expected Response:** `200 OK`
```json
{
    "success": true,
    "message": "Invitation cancelled successfully."
}
```

**What Happens:**
- ✅ Status changed to `cancelled`
- ✅ Token becomes invalid
- ✅ Cannot be accepted anymore

#### Test Case 2: Cancel Already Accepted Invitation
**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "Cannot cancel already accepted invitation."
}
```

#### Test Case 3: Cancel Multi-Use Invitation (No Email/Phone)
**Expected Response:** `200 OK`
**Note:** Uses `close_without_contact` permission

---

## Public Endpoints

### 1. Validate Token

**Endpoint:** `GET /api/v1/public/tenant-invitations/{token}/validate`

#### Test Case 1: Valid Token (Single-Use)
**Path:** `/api/v1/public/tenant-invitations/valid-token-64-chars/validate`

**Expected Response:** `200 OK`
```json
{
    "success": true,
    "message": "Invitation token is valid.",
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

#### Test Case 2: Valid Token (Multi-Use)
**Path:** `/api/v1/public/tenant-invitations/multi-use-token/validate`

**Expected Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "valid": true,
        "invitation": {
            "email": null,
            "name": "General Invitation",
            "ownership": {
                "name": "ABC Real Estate"
            },
            "expires_at": "2026-01-15 10:00:00"
        }
    }
}
```

#### Test Case 3: Invalid Token
**Path:** `/api/v1/public/tenant-invitations/invalid-token/validate`

**Expected Response:** `404 Not Found`
```json
{
    "success": false,
    "message": "Invalid invitation token."
}
```

#### Test Case 4: Expired Token
**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "This invitation has expired."
}
```

#### Test Case 5: Already Accepted Token (Single-Use)
**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "Invitation has already been accepted."
}
```

**Note:** Multi-use invitations can be validated multiple times

#### Test Case 6: Cancelled Token
**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "Invitation has been cancelled."
}
```

---

### 2. Accept Invitation

**Endpoint:** `POST /api/v1/public/tenant-invitations/{token}/accept`

#### Test Case 1: Accept Single-Use Invitation (New User)
**Path:** `/api/v1/public/tenant-invitations/valid-token/accept`

**Request Body:**
```json
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
    "income": 15000.00,
    "rating": "good",
    "notes": "New tenant registration"
}
```

**Expected Response:** `201 Created`
```json
{
    "success": true,
    "message": "Tenant invitation accepted successfully. Your account is ready.",
    "data": {
        "user": {
            "id": 10,
            "uuid": "770e8400-e29b-41d4-a716-446655440000",
            "email": "tenant@example.com",
            "name": "Ahmed Ali",
            "type": "tenant"
        },
        "tenant": {
            "id": 5,
            "user_id": 10,
            "ownership_id": 1,
            "national_id": "1234567890"
        },
        "invitation": {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "status": "accepted"
        },
        "access_token": "10|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "redirect_to": "/dashboard"
    }
}
```

**What Happens:**
- ✅ User account created
- ✅ Tenant profile created
- ✅ User assigned 'Tenant' role
- ✅ User linked to ownership (UserOwnershipMapping)
- ✅ Invitation marked as accepted
- ✅ Access token generated

#### Test Case 2: Accept Multi-Use Invitation
**Request Body:** Same as above

**Expected Response:** `201 Created` (same structure)

**What Happens:**
- ✅ User account created
- ✅ Tenant profile created
- ✅ Invitation remains `pending` (not marked as accepted)
- ✅ Multiple tenants can use same token

#### Test Case 3: Accept with Existing User Account
**Request Body:** Same as above, but email already exists

**Expected Response:** `201 Created`
**What Happens:**
- ✅ Uses existing user account
- ✅ Creates tenant profile
- ✅ Updates user type to 'tenant' if needed
- ✅ Assigns 'Tenant' role if missing

#### Test Case 4: Email Mismatch (Single-Use)
**Request Body:**
```json
{
    "email": "different@example.com", // Different from invitation
    "first_name": "Ahmed",
    "last_name": "Ali",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!"
}
```

**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "Email does not match invitation."
}
```

**Note:** Multi-use invitations don't check email match

#### Test Case 5: Tenant Already Exists
**Request Body:** Same email, same ownership

**Expected Response:** `400 Bad Request`
```json
{
    "success": false,
    "message": "Tenant already exists for this ownership."
}
```

#### Test Case 6: Validation Errors
**Request Body:**
```json
{
    "first_name": "", // Empty
    "email": "invalid-email", // Invalid format
    "password": "123" // Too short
}
```

**Expected Response:** `422 Unprocessable Entity`
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "first_name": ["The first name field is required."],
        "email": ["The email must be a valid email address."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

#### Test Case 7: Password Mismatch
**Request Body:**
```json
{
    "password": "SecurePassword123!",
    "password_confirmation": "DifferentPassword123!"
}
```

**Expected Response:** `422 Unprocessable Entity`
```json
{
    "errors": {
        "password_confirmation": ["The password confirmation does not match."]
    }
}
```

---

## Error Scenarios

### Authentication Errors

#### 401 Unauthorized - Missing Token
**Request:** Any owner endpoint without Authorization header

**Expected Response:**
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

#### 401 Unauthorized - Invalid Token
**Request:** Owner endpoint with invalid token

**Expected Response:**
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

#### 403 Forbidden - No Permission
**Request:** Owner endpoint without required permission

**Expected Response:**
```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```

#### 400 Bad Request - Missing Ownership Scope
**Request:** Owner endpoint without ownership cookie

**Expected Response:**
```json
{
    "success": false,
    "message": "Ownership scope is required."
}
```

---

## Edge Cases

### 1. Multi-Use Invitation Flow

**Scenario:** Owner creates invitation without email/phone, shares link publicly

**Steps:**
1. Owner creates invitation: `POST /api/v1/tenants/invitations/generate-link` (no email/phone)
2. Owner shares link on website
3. Tenant 1 accepts: `POST /api/v1/public/tenant-invitations/{token}/accept`
   - ✅ Tenant 1 created
   - ✅ Invitation status: `pending` (not accepted)
4. Tenant 2 accepts: Same token
   - ✅ Tenant 2 created
   - ✅ Invitation status: Still `pending`
5. Owner views invitation: `GET /api/v1/tenants/invitations/{uuid}`
   - ✅ Shows `tenants_count: 2`
   - ✅ Shows list of tenants
6. Owner cancels invitation: `POST /api/v1/tenants/invitations/{uuid}/cancel`
   - ✅ Status: `cancelled`
   - ✅ No more tenants can join

---

### 2. Single-Use Invitation Flow

**Scenario:** Owner invites specific tenant by email

**Steps:**
1. Owner creates invitation: `POST /api/v1/tenants/invitations` (with email)
2. Email sent automatically
3. Tenant validates token: `GET /api/v1/public/tenant-invitations/{token}/validate`
   - ✅ Token valid
4. Tenant accepts: `POST /api/v1/public/tenant-invitations/{token}/accept`
   - ✅ Tenant created
   - ✅ Invitation status: `accepted`
5. Another user tries to use same token:
   - ❌ `400 Bad Request`: "Invitation has already been accepted"

---

### 3. Expired Invitation

**Scenario:** Invitation expires before tenant accepts

**Steps:**
1. Owner creates invitation with `expires_in_days: 1`
2. Wait 1 day + 1 minute
3. Tenant tries to validate: `GET /api/v1/public/tenant-invitations/{token}/validate`
   - ❌ `400 Bad Request`: "This invitation has expired"
4. Tenant tries to accept:
   - ❌ `400 Bad Request`: "Invitation has expired"

---

### 4. User Already Exists

**Scenario:** User with email already exists in system

**Steps:**
1. User exists with email `tenant@example.com` (not as tenant)
2. Owner creates invitation for `tenant@example.com`
3. User accepts invitation:
   - ✅ Uses existing user account
   - ✅ Creates tenant profile
   - ✅ Updates user type to 'tenant'
   - ✅ Assigns 'Tenant' role

---

### 5. Tenant Already Exists for Ownership

**Scenario:** User is already a tenant for this ownership

**Steps:**
1. User is already tenant for Ownership A
2. Owner of Ownership A creates another invitation for same email
3. User tries to accept:
   - ❌ `400 Bad Request`: "Tenant already exists for this ownership"

**Note:** User can be tenant for multiple ownerships, but not duplicate for same ownership

---

## Complete Flow Examples

### Flow 1: Owner Invites Tenant via Email

```
1. Owner Login
   POST /api/v1/auth/login
   → Get access_token

2. Owner Creates Invitation
   POST /api/v1/tenants/invitations
   {
       "email": "tenant@example.com",
       "name": "Ahmed Ali",
       "expires_in_days": 7
   }
   → Invitation created, email sent

3. Tenant Receives Email
   → Clicks link: /register/tenant?token=xxx

4. Frontend Validates Token
   GET /api/v1/public/tenant-invitations/{token}/validate
   → Token valid, show registration form

5. Tenant Submits Registration
   POST /api/v1/public/tenant-invitations/{token}/accept
   {
       "first_name": "Ahmed",
       "last_name": "Ali",
       "email": "tenant@example.com",
       "password": "SecurePassword123!",
       ...
   }
   → Account created, token returned

6. Frontend Redirects
   → Save token, redirect to /dashboard
```

### Flow 2: Owner Shares Public Link

```
1. Owner Login
   POST /api/v1/auth/login

2. Owner Generates Link
   POST /api/v1/tenants/invitations/generate-link
   {
       "name": "Website Invitation",
       "expires_in_days": 30
   }
   → Link generated: /register/tenant?token=xxx

3. Owner Shares Link
   → Posts on website, WhatsApp, etc.

4. Multiple Tenants Use Link
   → Each tenant can register independently
   → All linked to same invitation
   → Owner sees all tenants in invitation details

5. Owner Closes Invitation
   POST /api/v1/tenants/invitations/{uuid}/cancel
   → No more tenants can join
```

### Flow 3: Bulk Invitations

```
1. Owner Login
   POST /api/v1/auth/login

2. Owner Creates Bulk Invitations
   POST /api/v1/tenants/invitations/bulk
   {
       "invitations": [
           {"email": "tenant1@example.com", "name": "One"},
           {"email": "tenant2@example.com", "name": "Two"},
           {"email": "tenant3@example.com", "name": "Three"}
       ],
       "expires_in_days": 7
   }
   → All invitations created, emails sent

3. Each Tenant Receives Email
   → Each follows Flow 1 steps independently

4. Owner Views All Invitations
   GET /api/v1/tenants/invitations?status=pending
   → See all pending invitations

5. Owner Resends Failed Invitations
   POST /api/v1/tenants/invitations/{uuid}/resend
   → Email sent again
```

---

## Testing Checklist

### Owner Endpoints
- [ ] Login successfully
- [ ] List invitations with filters
- [ ] Create single invitation (email)
- [ ] Create single invitation (phone)
- [ ] Create single invitation (both)
- [ ] Create multi-use invitation (no email/phone)
- [ ] Create bulk invitations (success)
- [ ] Create bulk invitations (duplicate emails - error)
- [ ] Generate link
- [ ] Show invitation details
- [ ] Show multi-use invitation with tenants
- [ ] Resend pending invitation
- [ ] Resend accepted invitation (error)
- [ ] Cancel pending invitation
- [ ] Cancel multi-use invitation

### Public Endpoints
- [ ] Validate valid token (single-use)
- [ ] Validate valid token (multi-use)
- [ ] Validate invalid token (error)
- [ ] Validate expired token (error)
- [ ] Validate cancelled token (error)
- [ ] Validate already accepted token (error)
- [ ] Accept invitation (new user)
- [ ] Accept invitation (existing user)
- [ ] Accept multi-use invitation
- [ ] Accept with email mismatch (error)
- [ ] Accept with tenant already exists (error)
- [ ] Accept with validation errors

### Error Handling
- [ ] 401 Unauthorized (no token)
- [ ] 401 Unauthorized (invalid token)
- [ ] 403 Forbidden (no permission)
- [ ] 400 Bad Request (no ownership scope)
- [ ] 404 Not Found (invalid UUID)
- [ ] 422 Validation errors

---

## Environment Variables

```env
base_url=http://localhost:8000
access_token= (auto-filled after login)
invitation_uuid= (auto-filled after create)
invitation_token= (auto-filled after create)
```

---

## Notes

1. **Cookies:** Refresh token and ownership_uuid are stored in httpOnly cookies, not in JSON responses
2. **Email Logging:** In development, all emails are logged to `storage/logs/emails.log`
3. **Multi-Use Invitations:** Can be accepted multiple times until manually cancelled
4. **Single-Use Invitations:** Can only be accepted once
5. **Token Format:** All tokens are 64 characters long
6. **UUID Format:** All UUIDs follow standard UUID v4 format

---

**Last Updated:** December 15, 2025  
**Version:** 1.0

