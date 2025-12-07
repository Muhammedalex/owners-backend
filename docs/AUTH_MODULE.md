# Auth Module - Implementation Summary

## Overview
A secure, advanced authentication module built with Laravel Sanctum, featuring refresh tokens, clean architecture, and permission-based access control.

## Architecture

### Folder Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── AuthController.php
│   ├── Requests/
│   │   └── Auth/
│   │       ├── RegisterRequest.php
│   │       ├── LoginRequest.php
│   │       ├── RefreshTokenRequest.php
│   │       ├── VerifyEmailRequest.php
│   │       ├── ForgotPasswordRequest.php
│   │       └── ResetPasswordRequest.php
│   └── Resources/
│       └── UserResource.php
├── Models/
│   └── User.php
├── Policies/
│   └── UserPolicy.php
├── Repositories/
│   ├── UserRepositoryInterface.php
│   └── UserRepository.php
├── Services/
│   └── AuthService.php
└── Traits/
    ├── HasUuid.php
    ├── GeneratesTokens.php
    └── LogsActivity.php
```

## Features

### ✅ Implemented

1. **User Registration**
   - Email/Phone registration
   - Password hashing
   - Email verification
   - UUID generation

2. **Authentication**
   - Email or Phone login
   - Rate limiting (5 attempts per minute)
   - Login attempt tracking
   - Account lockout protection
   - Active account checking

3. **Token Management**
   - Access tokens (short-lived, configurable)
   - Refresh tokens (long-lived, 30 days default)
   - Token refresh endpoint
   - Logout (single device)
   - Logout all devices
   - Device tracking (name, IP, user agent)

4. **Security Features**
   - Password hashing (bcrypt)
   - Token expiration
   - Refresh token rotation
   - IP address tracking
   - User agent tracking
   - Rate limiting

5. **Email Verification**
   - Email verification on registration
   - Resend verification email
   - Verification endpoint

6. **Password Reset**
   - Forgot password
   - Reset password with token

7. **User Management**
   - Repository pattern
   - Service layer
   - Policy-based authorization
   - Clean separation of concerns

## API Endpoints

### Public Endpoints

```
POST   /api/auth/register          - Register new user
POST   /api/auth/login             - Login user
POST   /api/auth/refresh           - Refresh access token
POST   /api/auth/forgot-password   - Request password reset
POST   /api/auth/reset-password    - Reset password
GET    /api/auth/verify-email/{id}/{hash} - Verify email
```

### Protected Endpoints (Requires Authentication)

```
POST   /api/auth/logout            - Logout current device
POST   /api/auth/logout-all        - Logout all devices
GET    /api/auth/me                - Get current user
POST   /api/auth/resend-verification - Resend verification email
```

## Configuration

### Environment Variables

Add to `.env`:

```env
SANCTUM_EXPIRATION=60              # Access token expiration in minutes
SANCTUM_REFRESH_EXPIRATION=30      # Refresh token expiration in days
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000
```

### Sanctum Config

Located in `config/sanctum.php`:
- Access token expiration: 60 minutes (default)
- Refresh token expiration: 30 days (default)

## Database

### Migrations

1. `create_users_table` - Base users table
2. `add_custom_fields_to_users_table` - Custom user fields
3. `create_personal_access_tokens_table` - Sanctum tokens with refresh token support
4. `create_permission_tables` - Spatie permissions

### User Table Fields

- `uuid` - Unique identifier
- `type` - User type
- `email` - Email address
- `phone` - Phone number
- `phone_verified_at` - Phone verification timestamp
- `password` - Hashed password
- `first` - First name
- `last` - Last name
- `company` - Company name
- `avatar` - Avatar path
- `active` - Active status
- `last_login_at` - Last login timestamp
- `attempts` - Login attempts counter
- `timezone` - User timezone (default: Asia/Riyadh)
- `locale` - User locale (default: ar)

## Usage Examples

### Register

```bash
POST /api/auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "first": "John",
  "last": "Doe",
  "device_name": "iPhone 12"
}
```

### Login

```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "device_name": "iPhone 12"
}
```

### Refresh Token

```bash
POST /api/auth/refresh
Content-Type: application/json

{
  "refresh_token": "your_refresh_token_here"
}
```

### Get Current User

```bash
GET /api/auth/me
Authorization: Bearer {access_token}
```

## Security Best Practices

1. **Tokens**
   - Access tokens are short-lived (60 minutes)
   - Refresh tokens are long-lived (30 days)
   - Refresh tokens are hashed in database
   - Tokens are rotated on refresh

2. **Rate Limiting**
   - Login attempts: 5 per minute per identifier
   - Automatic lockout after failed attempts

3. **Password Security**
   - Bcrypt hashing
   - Password confirmation required
   - Strong password validation

4. **Account Security**
   - Active status checking
   - Email verification required
   - Login attempt tracking

## Next Steps

1. ✅ Complete auth module
2. ⏳ Create permission seeders
3. ⏳ Create role seeders
4. ⏳ Add phone verification (SMS)
5. ⏳ Add 2FA support
6. ⏳ Add social authentication
7. ⏳ Add API documentation (Swagger/OpenAPI)

## Notes

- All access control is permission-based (not role-based)
- Roles are organizational tools only
- Super Admin can manage all roles and permissions
- All actions are logged for audit purposes

