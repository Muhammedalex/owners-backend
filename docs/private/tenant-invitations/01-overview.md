# Tenant Invitation Feature - Overview

## Feature Description

The Tenant Self-Registration via Invitation feature allows property owners to send secure invitation links to potential tenants. Tenants can then register themselves independently by clicking the invitation link and completing a registration form. This eliminates the need for manual tenant creation and streamlines the onboarding process.

---

## Business Requirements

### User Stories

1. **As an Owner**, I want to send invitation links to potential tenants so they can register themselves.
2. **As a Tenant**, I want to receive an invitation link via email and complete my registration independently.
3. **As a System**, I want to ensure invitation links are secure, time-limited, and ownership-scoped.

### Key Requirements

- ✅ Owner can generate invitation links for tenants
- ✅ Invitation links can be sent via email or generated as shareable links
- ✅ Links are secure (token-based, time-limited)
- ✅ Tenant completes registration form independently
- ✅ Automatic user account creation upon registration
- ✅ Automatic tenant profile creation linked to ownership
- ✅ Automatic user type assignment (tenant)
- ✅ Automatic role assignment (Tenant role)
- ✅ Automatic ownership mapping
- ✅ Invitation tracking and status management
- ✅ Support for single-use and multi-use invitations
- ✅ Ownership-specific email configuration

---

## Key Concepts

### Invitation Types

#### 1. Single-use Invitations (With Email/Phone)
- **Characteristics:** Has `email` or `phone` field set
- **Behavior:** Can only be accepted once
- **Status:** Changes to `accepted` after first use
- **Use Case:** Inviting a specific person via email/SMS

#### 2. Multi-use Invitations (Without Email/Phone)
- **Characteristics:** Both `email` and `phone` are `null`
- **Behavior:** Can be accepted by multiple tenants
- **Status:** Remains `pending` until manually closed by owner
- **Use Case:** Public invitation link shared on website/social media

### Invitation Statuses

- **`pending`** - Invitation is active and can be accepted
- **`accepted`** - Invitation has been used (single-use only)
- **`expired`** - Invitation has passed its expiration date
- **`cancelled`** - Invitation was cancelled by owner

### Invitation Lifecycle

```
Created → Pending → Accepted/Expired/Cancelled
```

---

## Architecture Overview

### Components

1. **Models**
   - `TenantInvitation` - Stores invitation data
   - `Tenant` - Links to invitation via `invitation_id`

2. **Services**
   - `TenantInvitationService` - Business logic for invitations
   - `OwnershipMailService` - Handles ownership-specific mail configuration
   - `AuthService` - User registration
   - `UserOwnershipMappingService` - Links users to ownerships

3. **Controllers**
   - `TenantInvitationController` - Owner-facing endpoints (authenticated)
   - `PublicTenantInvitationController` - Public endpoints (no auth)

4. **Policies**
   - `TenantInvitationPolicy` - Authorization rules

5. **Mail**
   - `TenantInvitationMail` - Email template for invitations

---

## Data Flow

### Creating an Invitation

```
Owner → API Request → TenantInvitationController
  ↓
TenantInvitationService.create()
  ↓
Generate Token (64 chars)
  ↓
Create TenantInvitation Record
  ↓
Send Email (if email provided) → OwnershipMailService
  ↓
Return Invitation Data
```

### Accepting an Invitation

```
Tenant → Public API → PublicTenantInvitationController
  ↓
TenantInvitationService.acceptInvitation()
  ↓
Validate Token & Expiration
  ↓
Create/Update User Account
  ↓
Set User Type: 'tenant'
  ↓
Assign Role: 'Tenant'
  ↓
Create Tenant Profile
  ↓
Link User to Ownership (UserOwnershipMapping)
  ↓
Update Invitation Status
  ↓
Generate Auth Tokens
  ↓
Return Success Response
```

---

## Security Features

1. **Token Security**
   - 64-character random tokens
   - Unique per invitation
   - Stored in database (not hashed, but unique)

2. **Expiration**
   - Default: 7 days
   - Configurable: 1-30 days
   - Automatic expiration check

3. **Email Validation**
   - Email must match invitation email (for single-use)
   - Prevents unauthorized registrations

4. **Ownership Scoping**
   - All invitations are ownership-scoped
   - Users can only access invitations for their ownerships
   - Public endpoints validate token ownership

5. **Rate Limiting**
   - Laravel's built-in rate limiting
   - Prevents abuse

---

## Integration Points

### With Other Modules

1. **Auth Module**
   - User creation
   - Email verification (optional)
   - Token generation

2. **Ownership Module**
   - Ownership scoping
   - User-ownership mapping

3. **Settings Module**
   - Mail configuration per ownership
   - Notification settings

4. **Tenant Module**
   - Tenant profile creation
   - Tenant data management

---

## Configuration

### Environment Variables

```env
# Frontend URL for invitation links
FRONTEND_URL=https://app.example.com

# Email verification (optional)
AUTH_EMAIL_VERIFICATION_ENABLED=false

# Mail configuration (system default)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
```

### Ownership Settings

Mail settings can be configured per ownership via `system_settings` table:
- `smtp_host`
- `smtp_port`
- `smtp_username`
- `smtp_password`
- `smtp_encryption`
- `email_from_address`
- `email_from_name`

---

## Future Enhancements

1. **SMS Invitations** - Send invitations via SMS
2. **QR Code Generation** - Generate QR codes for invitation links
3. **Custom Email Templates** - Per-ownership email templates
4. **Invitation Analytics** - Track acceptance rates
5. **Bulk Import** - Import invitations from CSV/Excel
6. **Scheduled Invitations** - Schedule invitations for future dates

---

## Related Files

- **Migration:** `database/migrations/2025_12_15_070612_create_tenant_invitations_table.php`
- **Model:** `app/Models/V1/Tenant/TenantInvitation.php`
- **Service:** `app/Services/V1/Tenant/TenantInvitationService.php`
- **Controller (Owner):** `app/Http/Controllers/Api/V1/Tenant/TenantInvitationController.php`
- **Controller (Public):** `app/Http/Controllers/Api/V1/Tenant/PublicTenantInvitationController.php`
- **Policy:** `app/Policies/V1/Tenant/TenantInvitationPolicy.php`
- **Mail:** `app/Mail/V1/Tenant/TenantInvitationMail.php`

