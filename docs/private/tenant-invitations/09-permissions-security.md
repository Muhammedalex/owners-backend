# Permissions & Security

## Overview

This document describes the permissions, policies, and security measures for the Tenant Invitation feature.

---

## Permissions

### Permission List

| Permission | Description | Scope |
|------------|-------------|-------|
| `tenants.invitations.view` | View invitations | Ownership-scoped |
| `tenants.invitations.create` | Create invitations | Ownership-scoped |
| `tenants.invitations.update` | Update invitations | Ownership-scoped |
| `tenants.invitations.delete` | Delete invitations | Ownership-scoped |
| `tenants.invitations.cancel` | Cancel invitations | Ownership-scoped |
| `tenants.invitations.resend` | Resend invitation emails | Ownership-scoped |
| `tenants.invitations.close_without_contact` | Close multi-use invitations | Ownership-scoped |

### Permission Usage

#### View Invitations
- **Permission:** `tenants.invitations.view`
- **Used In:** `TenantInvitationController::index()`, `show()`
- **Policy Method:** `viewAny()`, `view()`

#### Create Invitations
- **Permission:** `tenants.invitations.create`
- **Used In:** `TenantInvitationController::store()`, `storeBulk()`, `generateLink()`
- **Policy Method:** `create()`

#### Cancel Invitations
- **Permission:** `tenants.invitations.cancel` (for single-use)
- **Permission:** `tenants.invitations.close_without_contact` (for multi-use)
- **Used In:** `TenantInvitationController::cancel()`
- **Policy Method:** `cancel()`, `closeWithoutContact()`

#### Resend Invitations
- **Permission:** `tenants.invitations.resend`
- **Used In:** `TenantInvitationController::resend()`
- **Policy Method:** `resend()`

---

## Policy: TenantInvitationPolicy

**Location:** `app/Policies/V1/Tenant/TenantInvitationPolicy.php`

### Policy Methods

#### 1. viewAny()

**Purpose:** Check if user can view invitations list

**Logic:**
- Super Admin: Requires `tenants.invitations.view` permission
- Regular User: Requires `tenants.invitations.view` AND has ownership mappings

```php
public function viewAny(User $user): bool
{
    if ($user->isSuperAdmin()) {
        return $user->can('tenants.invitations.view');
    }
    return $user->can('tenants.invitations.view') && $user->ownershipMappings()->exists();
}
```

#### 2. view()

**Purpose:** Check if user can view specific invitation

**Logic:**
- Super Admin: Requires `tenants.invitations.view` permission
- Regular User: Requires `tenants.invitations.view` AND has access to invitation's ownership

```php
public function view(User $user, TenantInvitation $invitation): bool
{
    if ($user->isSuperAdmin()) {
        return $user->can('tenants.invitations.view');
    }
    return $user->can('tenants.invitations.view') && $user->hasOwnership($invitation->ownership_id);
}
```

#### 3. create()

**Purpose:** Check if user can create invitations

**Logic:**
- Super Admin: Requires `tenants.invitations.create` permission
- Regular User: Requires `tenants.invitations.create` AND has ownership mappings

```php
public function create(User $user): bool
{
    if ($user->isSuperAdmin()) {
        return $user->can('tenants.invitations.create');
    }
    return $user->can('tenants.invitations.create') && $user->ownershipMappings()->exists();
}
```

#### 4. cancel()

**Purpose:** Check if user can cancel single-use invitations

**Logic:**
- Super Admin: Requires `tenants.invitations.cancel` permission
- Regular User: Requires `tenants.invitations.cancel` AND has access to invitation's ownership

```php
public function cancel(User $user, TenantInvitation $invitation): bool
{
    if ($user->isSuperAdmin()) {
        return $user->can('tenants.invitations.cancel');
    }
    return $user->can('tenants.invitations.cancel') && $user->hasOwnership($invitation->ownership_id);
}
```

#### 5. closeWithoutContact()

**Purpose:** Check if user can close multi-use invitations (without email/phone)

**Logic:**
- Only works for invitations without email AND phone
- Super Admin: Requires `tenants.invitations.close_without_contact` permission
- Regular User: Requires `tenants.invitations.close_without_contact` AND has access to invitation's ownership

```php
public function closeWithoutContact(User $user, TenantInvitation $invitation): bool
{
    // Only allow if invitation has no email and no phone
    if ($invitation->email || $invitation->phone) {
        return false;
    }
    
    if ($user->isSuperAdmin()) {
        return $user->can('tenants.invitations.close_without_contact');
    }
    return $user->can('tenants.invitations.close_without_contact') && $user->hasOwnership($invitation->ownership_id);
}
```

#### 6. resend()

**Purpose:** Check if user can resend invitation emails

**Logic:**
- Super Admin: Requires `tenants.invitations.resend` permission
- Regular User: Requires `tenants.invitations.resend` AND has access to invitation's ownership

```php
public function resend(User $user, TenantInvitation $invitation): bool
{
    if ($user->isSuperAdmin()) {
        return $user->can('tenants.invitations.resend');
    }
    return $user->can('tenants.invitations.resend') && $user->hasOwnership($invitation->ownership_id);
}
```

---

## Security Measures

### 1. Token Security

**Token Generation:**
```php
do {
    $token = Str::random(64);
} while ($this->invitationRepository->findByToken($token) !== null);
```

**Characteristics:**
- 64-character random string
- Cryptographically secure (Laravel's `Str::random()`)
- Unique per invitation
- Stored in database (not hashed, but unique constraint prevents duplicates)

**Security Considerations:**
- Tokens are long enough to prevent brute force
- Unique constraint prevents token collisions
- Tokens expire after set time
- Tokens become invalid after acceptance (single-use)

---

### 2. Expiration Management

**Default Expiration:** 7 days

**Configurable:** 1-30 days

**Automatic Expiration:**
- System checks expiration on every token validation
- Expired invitations cannot be accepted
- Status can be marked as `expired` (future scheduled job)

**Security Benefits:**
- Limits window of opportunity for token misuse
- Reduces risk of long-lived tokens being compromised
- Forces timely registration

---

### 3. Email Validation

**Single-use Invitations:**
- Registration email must match invitation email
- Prevents unauthorized users from accepting invitations
- Validated in `TenantInvitationService::acceptInvitation()`

```php
if ($invitation->email && $invitation->email !== $registrationData['email']) {
    throw new \Exception('Email does not match invitation.');
}
```

**Multi-use Invitations:**
- No email validation (no email to match)
- Anyone with link can register

---

### 4. Ownership Scoping

**All Owner Endpoints:**
- Require `ownership_uuid` cookie
- Middleware validates ownership access
- Users can only access invitations for their ownerships

**Public Endpoints:**
- No authentication required
- Token validation ensures ownership scope
- Token links to specific ownership

---

### 5. Rate Limiting

**Laravel Built-in:**
- API rate limiting via middleware
- Prevents abuse of endpoints
- Configurable per route

**Recommendations:**
- Limit invitation creation: 10 per minute per user
- Limit registration attempts: 5 per minute per IP
- Limit token validation: 20 per minute per IP

---

### 6. CSRF Protection

**Public Endpoints:**
- Token-based validation (no session)
- No CSRF token required
- Token itself acts as authorization

**Owner Endpoints:**
- Protected by Sanctum authentication
- CSRF protection via Laravel middleware

---

### 7. Input Validation

**Request Validation:**
- All inputs validated via FormRequest classes
- Email format validation
- Phone format validation (Saudi)
- Password strength requirements
- SQL injection prevention (Eloquent ORM)

**Validation Classes:**
- `StoreTenantInvitationRequest`
- `StoreBulkTenantInvitationRequest`
- `AcceptTenantInvitationRequest`

---

## Authorization Flow

### Owner Endpoints Flow

```
Request → Authentication Check (Sanctum)
  ↓
Ownership Scope Check (Middleware)
  ↓
Policy Check (TenantInvitationPolicy)
  ↓
Permission Check (Spatie)
  ↓
Controller Action
```

### Public Endpoints Flow

```
Request → Token Validation
  ↓
Invitation Lookup
  ↓
Status Check (expired, cancelled, accepted)
  ↓
Controller Action
```

---

## Role-Based Access

### Super Admin

**Access:**
- Can view all invitations (all ownerships)
- Can create invitations for any ownership
- Can manage all invitations
- Bypasses ownership scope restrictions

**Permissions Required:**
- All invitation permissions

### Owner

**Access:**
- Can view invitations for their ownerships
- Can create invitations for their ownerships
- Can manage invitations for their ownerships
- Restricted to assigned ownerships

**Permissions Required:**
- `tenants.invitations.view`
- `tenants.invitations.create`
- `tenants.invitations.cancel`
- `tenants.invitations.resend`
- `tenants.invitations.close_without_contact` (for multi-use)

### Manager/Staff

**Access:**
- Depends on assigned permissions
- Same ownership scope restrictions as Owner

**Permissions Required:**
- Assigned by Super Admin/Owner

---

## Security Best Practices

### For Owners

1. **Token Sharing:**
   - Don't share invitation links publicly (single-use)
   - Use multi-use invitations for public sharing
   - Monitor invitation acceptance

2. **Expiration:**
   - Use appropriate expiration times
   - Shorter for sensitive invitations
   - Longer for public invitations

3. **Email Security:**
   - Verify email addresses before sending
   - Use secure email providers
   - Monitor email delivery

### For Tenants

1. **Link Security:**
   - Don't share invitation links
   - Use link only once (single-use)
   - Report suspicious links

2. **Password Security:**
   - Use strong passwords
   - Don't reuse passwords
   - Enable 2FA if available

---

## Audit Trail

### Tracked Information

**Invitation Creation:**
- `invited_by` - User who created invitation
- `created_at` - Creation timestamp
- `ownership_id` - Ownership scope

**Invitation Acceptance:**
- `accepted_by` - User who accepted (single-use)
- `accepted_at` - Acceptance timestamp
- `tenant_id` - Created tenant (single-use)

**Multi-use Tracking:**
- `tenants` relationship - All tenants who joined
- `updated_at` - Last acceptance timestamp

---

## Related Files

- **Policy:** `app/Policies/V1/Tenant/TenantInvitationPolicy.php`
- **Controller:** `app/Http/Controllers/Api/V1/Tenant/TenantInvitationController.php`
- **Service:** `app/Services/V1/Tenant/TenantInvitationService.php`
- **Permissions:** `database/seeders/V1/Auth/PermissionSeeder.php`

---

## Related Documentation

- **[Overview](./01-overview.md)**
- **[API Endpoints - Owner](./03-api-endpoints-owner.md)**
- **[Invitation Types](./07-invitation-types.md)**

