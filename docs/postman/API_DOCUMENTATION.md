# Auth API Documentation - V1

## Overview

This document provides comprehensive documentation for the Authentication API endpoints. All endpoints are versioned under `/api/v1/auth/`.

**Base URL:** `http://localhost:8000/api/v1/auth`

**API Version:** 1.0

---

## Authentication

### Token-Based Authentication

The API uses Laravel Sanctum for token-based authentication. After successful login or registration, you'll receive:

- **Access Token**: Short-lived token (60 minutes default) for API requests
- **Refresh Token**: Long-lived token (30 days default) for refreshing access tokens

### Using Tokens

Include the access token in the Authorization header:

```
Authorization: Bearer {access_token}
```

---

## Response Format

### Success Response

All successful responses follow this format:

```json
{
    "success": true,
    "message": "Operation successful message",
    "data": {
        // Response data
    }
}
```

### Error Response

All error responses follow this format:

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        // Validation errors (if applicable)
    }
}
```

---

## HTTP Status Codes

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Authentication required or failed |
| 422 | Unprocessable Entity | Validation errors |
| 500 | Internal Server Error | Server error |

---

## Public Endpoints

### 1. Register

Register a new user account.

**Endpoint:** `POST /api/v1/auth/register`

**Authentication:** Not required

**Request Body:**

```json
{
    "email": "user@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "first": "John",
    "last": "Doe",
    "phone": "+966501234567",
    "company": "Example Company",
    "type": "owner",
    "device_name": "iPhone 14 Pro"
}
```

**Required Fields:**
- `email` (string, email, unique): User email address
- `password` (string, min:8): User password
- `password_confirmation` (string): Must match password

**Optional Fields:**
- `phone` (string, max:20, unique): Phone number
- `first` (string, max:100): First name
- `last` (string, max:100): Last name
- `company` (string, max:255): Company name
- `type` (string, max:50): User type (default: 'user')
- `device_name` (string, max:255): Device identifier

**Success Response (201):**

```json
{
    "success": true,
    "message": "Registration successful. Please verify your email.",
    "data": {
        "user": {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "user@example.com",
            "phone": "+966501234567",
            "name": "John Doe",
            "first": "John",
            "last": "Doe",
            "company": "Example Company",
            "type": "owner",
            "active": true,
            "email_verified_at": null,
            "last_login_at": null,
            "timezone": "Asia/Riyadh",
            "locale": "ar",
            "roles": [],
            "created_at": "2025-01-01T00:00:00.000000Z",
            "updated_at": "2025-01-01T00:00:00.000000Z"
        },
        "tokens": {
            "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "refresh_token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "token_type": "Bearer",
            "expires_in": 3600
        }
    }
}
```

**Error Responses:**

**422 - Validation Error:**
```json
{
    "message": "The email has already been taken. (and 1 more error)",
    "errors": {
        "email": ["This email is already registered."],
        "password": ["The password confirmation does not match."]
    }
}
```

**422 - Email Already Exists:**
```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": ["This email is already registered."]
    }
}
```

---

### 2. Login

Authenticate a user and receive access tokens.

**Endpoint:** `POST /api/v1/auth/login`

**Authentication:** Not required

**Request Body (Email Login):**

```json
{
    "email": "user@example.com",
    "password": "SecurePassword123!",
    "device_name": "iPhone 14 Pro"
}
```

**Request Body (Phone Login):**

```json
{
    "phone": "+966501234567",
    "password": "SecurePassword123!",
    "device_name": "iPhone 14 Pro"
}
```

**Required Fields:**
- `email` OR `phone`: User identifier (one is required)
- `password` (string): User password

**Optional Fields:**
- `device_name` (string): Device identifier

**Success Response (200):**

```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "user": {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "user@example.com",
            "name": "John Doe",
            "active": true,
            "email_verified_at": "2025-01-01T00:00:00.000000Z",
            "last_login_at": "2025-01-01T12:00:00.000000Z",
            "roles": ["owner"],
            "permissions": []
        },
        "tokens": {
            "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "refresh_token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "token_type": "Bearer",
            "expires_in": 3600
        }
    }
}
```

**Error Responses:**

**401 - Invalid Credentials:**
```json
{
    "success": false,
    "message": "Invalid credentials."
}
```

**401 - Account Deactivated:**
```json
{
    "success": false,
    "message": "Your account has been deactivated."
}
```

**422 - Too Many Attempts:**
```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "email": ["Too many login attempts. Please try again in 45 seconds."]
    }
}
```

**Rate Limiting:**
- Maximum 5 login attempts per minute per identifier (email/phone)
- Account locked temporarily after failed attempts
- Lockout duration: 60 seconds

---

### 3. Refresh Token

Refresh an expired access token using a valid refresh token.

**Endpoint:** `POST /api/v1/auth/refresh`

**Authentication:** Not required (uses refresh token)

**Request Body:**

```json
{
    "refresh_token": "your_refresh_token_here"
}
```

**Required Fields:**
- `refresh_token` (string): Valid refresh token

**Success Response (200):**

```json
{
    "success": true,
    "message": "Token refreshed successfully.",
    "data": {
        "user": {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "user@example.com",
            "name": "John Doe"
        },
        "tokens": {
            "access_token": "2|yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy",
            "refresh_token": "yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy",
            "token_type": "Bearer",
            "expires_in": 3600
        }
    }
}
```

**Error Responses:**

**401 - Invalid/Expired Refresh Token:**
```json
{
    "success": false,
    "message": "Invalid or expired refresh token."
}
```

**Token Rotation:**
- Old refresh token is automatically revoked
- New access and refresh tokens are issued
- Device tracking is maintained

---

### 4. Forgot Password

Request a password reset link.

**Endpoint:** `POST /api/v1/auth/forgot-password`

**Authentication:** Not required

**Request Body:**

```json
{
    "email": "user@example.com"
}
```

**Required Fields:**
- `email` (string, email, exists): Registered email address

**Success Response (200):**

```json
{
    "success": true,
    "message": "Password reset link sent to your email."
}
```

**Error Responses:**

**422 - Email Not Found:**
```json
{
    "message": "The selected email is invalid.",
    "errors": {
        "email": ["The selected email is invalid."]
    }
}
```

---

### 5. Reset Password

Reset password using token from email.

**Endpoint:** `POST /api/v1/auth/reset-password`

**Authentication:** Not required

**Request Body:**

```json
{
    "token": "reset_token_from_email",
    "email": "user@example.com",
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
}
```

**Required Fields:**
- `token` (string): Password reset token from email
- `email` (string, email, exists): User email
- `password` (string, min:8): New password
- `password_confirmation` (string): Must match password

**Success Response (200):**

```json
{
    "success": true,
    "message": "Password reset successfully."
}
```

**Error Responses:**

**400 - Invalid Token:**
```json
{
    "success": false,
    "message": "Unable to reset password."
}
```

---

### 6. Verify Email

Verify user email address.

**Endpoint:** `GET /api/v1/auth/verify-email/{id}/{hash}`

**Authentication:** Not required

**URL Parameters:**
- `id` (integer): User ID
- `hash` (string): Email verification hash

**Success Response (200):**

```json
{
    "success": true,
    "message": "Email verified successfully."
}
```

**Error Responses:**

**400 - Invalid Link:**
```json
{
    "success": false,
    "message": "Invalid verification link."
}
```

---

## Protected Endpoints

All protected endpoints require authentication via Bearer token in the Authorization header.

### 7. Get Current User

Get authenticated user's profile.

**Endpoint:** `GET /api/v1/auth/me`

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "email": "user@example.com",
        "phone": "+966501234567",
        "name": "John Doe",
        "first": "John",
        "last": "Doe",
        "company": "Example Company",
        "type": "owner",
        "active": true,
        "email_verified_at": "2025-01-01T00:00:00.000000Z",
        "last_login_at": "2025-01-01T12:00:00.000000Z",
        "timezone": "Asia/Riyadh",
        "locale": "ar",
        "roles": ["owner"],
        "permissions": ["ownerships.view", "properties.view"],
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-01-01T00:00:00.000000Z"
    }
}
```

**Error Responses:**

**401 - Unauthenticated:**
```json
{
    "message": "Unauthenticated."
}
```

---

### 8. Logout

Logout from current device.

**Endpoint:** `POST /api/v1/auth/logout`

**Authentication:** Required

**Request Body (Optional):**

```json
{
    "refresh_token": "refresh_token_to_revoke"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Logged out successfully."
}
```

**Behavior:**
- If `refresh_token` provided: Revokes that specific token
- If no `refresh_token`: Revokes current access token

---

### 9. Logout All Devices

Logout from all devices.

**Endpoint:** `POST /api/v1/auth/logout-all`

**Authentication:** Required

**Success Response (200):**

```json
{
    "success": true,
    "message": "Logged out from all devices successfully."
}
```

---

### 10. Resend Verification Email

Resend email verification link.

**Endpoint:** `POST /api/v1/auth/resend-verification`

**Authentication:** Required

**Success Response (200):**

```json
{
    "success": true,
    "message": "Verification email sent successfully."
}
```

**Error Responses:**

**400 - Already Verified:**
```json
{
    "success": false,
    "message": "Email already verified."
}
```

---

## Error Codes Reference

### Authentication Errors

| Code | Message | Description |
|------|---------|-------------|
| 401 | Unauthenticated | No valid authentication token provided |
| 401 | Invalid credentials | Wrong email/phone or password |
| 401 | Your account has been deactivated | User account is inactive |
| 401 | Invalid or expired refresh token | Refresh token is invalid or expired |

### Validation Errors (422)

| Field | Error | Description |
|-------|-------|-------------|
| email | The email field is required | Email is missing |
| email | The email must be a valid email address | Invalid email format |
| email | The email has already been taken | Email already registered |
| email | Too many login attempts | Rate limit exceeded |
| password | The password field is required | Password is missing |
| password | The password must be at least 8 characters | Password too short |
| password | The password confirmation does not match | Passwords don't match |
| phone | The phone has already been taken | Phone already registered |

### Server Errors (500)

| Code | Description |
|------|-------------|
| 500 | Internal Server Error | Unexpected server error occurred |

---

## Security Features

### Token Security

1. **Access Tokens**
   - Short-lived (60 minutes default)
   - Stored securely in client
   - Automatically expire

2. **Refresh Tokens**
   - Long-lived (30 days default)
   - Hashed in database
   - Rotated on each refresh
   - Device tracking enabled

3. **Token Rotation**
   - Old tokens revoked on refresh
   - New tokens issued
   - Prevents token reuse

### Rate Limiting

- **Login Attempts**: 5 per minute per identifier
- **Automatic Lockout**: Temporary lockout after failed attempts
- **Lockout Duration**: 60 seconds

### Account Security

- **Active Status Check**: Inactive accounts cannot login
- **Email Verification**: Required for full account access
- **Login Attempt Tracking**: Monitors failed login attempts
- **Device Tracking**: Tracks IP, user agent, device name

---

## Best Practices

### Token Management

1. **Store tokens securely** - Never expose tokens in client-side code
2. **Refresh before expiry** - Refresh tokens before access token expires
3. **Handle token errors** - Implement proper error handling for expired tokens
4. **Logout properly** - Always logout when user session ends

### Error Handling

1. **Check status codes** - Always check HTTP status codes
2. **Handle validation errors** - Display field-specific errors to users
3. **Retry logic** - Implement retry for network errors
4. **User feedback** - Provide clear error messages

### Security

1. **Use HTTPS** - Always use HTTPS in production
2. **Validate input** - Validate all user input
3. **Protect tokens** - Never log or expose tokens
4. **Monitor activity** - Monitor for suspicious activity

---

## Postman Collection

Import the Postman collection from:
```
docs/postman/AUTH_API_COLLECTION.json
```

### Environment Variables

Set up these variables in Postman:

- `base_url`: `http://localhost:8000`
- `access_token`: (auto-set after login/register)
- `refresh_token`: (auto-set after login/register)

### Auto Token Management

The Postman collection includes scripts that automatically:
- Save tokens after login/register
- Use tokens in protected endpoints
- Refresh tokens when needed

---

## Support

For issues or questions:
- Check error responses for detailed messages
- Review validation errors for field-specific issues
- Verify token expiration and refresh tokens
- Ensure account is active and verified

