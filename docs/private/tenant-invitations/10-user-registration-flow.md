# User Registration Flow

## Overview

This document describes the complete flow of user creation, role assignment, and ownership mapping when a tenant accepts an invitation.

---

## Registration Flow Diagram

```
Tenant Accepts Invitation
  ↓
Validate Token & Expiration
  ↓
Check if User Exists
  ├─→ User Exists → Update User Type & Role
  └─→ User Doesn't Exist → Create New User
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

## Step-by-Step Process

### Step 1: Token Validation

**Location:** `TenantInvitationService::acceptInvitation()`

**Checks:**
- Token exists in database
- Invitation not expired
- Invitation not cancelled
- Invitation not already accepted (single-use only)

**Code:**
```php
$invitation = $this->invitationRepository->findByToken($token);

if (!$invitation) {
    throw new \Exception('Invalid invitation token.');
}

if ($invitation->isExpired()) {
    throw new \Exception('Invitation has expired.');
}

if ($invitation->isCancelled()) {
    throw new \Exception('Invitation has been cancelled.');
}

// For single-use: check if already accepted
if (($invitation->email || $invitation->phone) && $invitation->isAccepted()) {
    throw new \Exception('Invitation has already been accepted.');
}
```

---

### Step 2: Email Validation (Single-use)

**Location:** `TenantInvitationService::acceptInvitation()`

**Check:**
- Registration email must match invitation email (if invitation has email)

**Code:**
```php
if ($invitation->email && $invitation->email !== $registrationData['email']) {
    throw new \Exception('Email does not match invitation.');
}
```

**Note:** Multi-use invitations skip this check (no email to match).

---

### Step 3: User Account Handling

**Location:** `TenantInvitationService::acceptInvitation()`

#### Scenario A: New User

**Process:**
1. Call `AuthService::register()`
2. Create user account
3. Set `type` to `'tenant'`
4. Handle email verification (if enabled)

**Code:**
```php
$user = \App\Models\V1\Auth\User::where('email', $registrationData['email'])->first();

if (!$user) {
    // Create new user with tenant type
    $registerResult = $this->authService->register([
        'email' => $registrationData['email'],
        'phone' => $registrationData['phone'] ?? null,
        'first' => $registrationData['first_name'],
        'last' => $registrationData['last_name'],
        'password' => $registrationData['password'],
        'password_confirmation' => $registrationData['password_confirmation'],
        'type' => 'tenant', // Set user type
        'device_name' => 'web',
    ]);
    $user = $registerResult['user'];
    
    // Assign Tenant role
    $tenantRole = Role::where('name', 'Tenant')->first();
    if ($tenantRole) {
        $user->assignRole($tenantRole);
    }
}
```

**User Created With:**
- `type` = `'tenant'`
- `email` = Registration email
- `phone` = Registration phone (if provided)
- `first` = First name
- `last` = Last name
- `password` = Hashed password
- `email_verified_at` = Set based on config

---

#### Scenario B: Existing User

**Process:**
1. Find existing user by email
2. Check if tenant already exists for this ownership
3. Update user type if needed
4. Assign role if needed

**Code:**
```php
else {
    // Check if tenant already exists for this ownership
    $existingTenant = $this->tenantRepository->findByUserAndOwnership(
        $user->id,
        $invitation->ownership_id
    );

    if ($existingTenant) {
        throw new \Exception('Tenant already exists for this ownership.');
    }
    
    // If user exists but doesn't have tenant type/role, update it
    if ($user->type !== 'tenant') {
        $user->update(['type' => 'tenant']);
    }
    
    // Ensure user has Tenant role
    if (!$user->hasRole('Tenant')) {
        $tenantRole = Role::where('name', 'Tenant')->first();
        if ($tenantRole) {
            $user->assignRole($tenantRole);
        }
    }
}
```

**Checks:**
- Tenant doesn't already exist for this ownership
- User type is `'tenant'`
- User has `'Tenant'` role

---

### Step 4: Tenant Profile Creation

**Location:** `TenantInvitationService::acceptInvitation()`

**Process:**
1. Create tenant record
2. Link to user
3. Link to ownership
4. Link to invitation (`invitation_id`)
5. Store tenant-specific data

**Code:**
```php
$tenant = $this->tenantRepository->create([
    'user_id' => $user->id,
    'ownership_id' => $invitation->ownership_id,
    'invitation_id' => $invitation->id, // Track which invitation created this tenant
    'national_id' => $registrationData['national_id'] ?? null,
    'id_type' => $registrationData['id_type'] ?? 'national_id',
    'id_expiry' => $registrationData['id_expiry'] ?? null,
    'emergency_name' => $registrationData['emergency_name'] ?? null,
    'emergency_phone' => $registrationData['emergency_phone'] ?? null,
    'emergency_relation' => $registrationData['emergency_relation'] ?? null,
    'employment' => $registrationData['employment'] ?? null,
    'employer' => $registrationData['employer'] ?? null,
    'income' => $registrationData['income'] ?? null,
    'rating' => $registrationData['rating'] ?? 'good',
    'notes' => $registrationData['notes'] ?? null,
]);
```

**Tenant Created With:**
- `user_id` - Links to user account
- `ownership_id` - Links to ownership
- `invitation_id` - Links to invitation (for tracking)
- All tenant-specific data from registration form

---

### Step 5: Ownership Mapping

**Location:** `TenantInvitationService::acceptInvitation()`

**Process:**
1. Check if mapping already exists
2. Create mapping if doesn't exist
3. Set as default if first ownership

**Code:**
```php
// Link user to ownership (if not already linked)
try {
    $existingMapping = $this->mappingService->findByUserAndOwnership(
        $user->id,
        $invitation->ownership_id
    );

    if (!$existingMapping) {
        // Check if this is user's first ownership mapping (set as default)
        $userMappings = $this->mappingService->getByUser($user->id);
        $isDefault = $userMappings->isEmpty();

        $this->mappingService->create([
            'user_id' => $user->id,
            'ownership_id' => $invitation->ownership_id,
            'default' => $isDefault,
        ]);
    }
} catch (\Exception $e) {
    // Log but don't fail registration
    Log::warning("Failed to create ownership mapping: " . $e->getMessage());
}
```

**Mapping Created:**
- `user_id` - User ID
- `ownership_id` - Ownership ID
- `default` - `true` if first ownership, `false` otherwise
- `created_at` - Timestamp

**Benefits:**
- User can access ownership-scoped data
- Ownership scope middleware recognizes user
- User can switch between ownerships (if multiple)

---

### Step 6: Invitation Status Update

**Location:** `TenantInvitationService::acceptInvitation()`

#### Single-use Invitations (With Email/Phone)

**Process:**
1. Mark invitation as `accepted`
2. Set `accepted_by` to user ID
3. Set `tenant_id` to created tenant ID
4. Set `accepted_at` timestamp

**Code:**
```php
if ($invitation->email || $invitation->phone) {
    // Single-use invitation: mark as accepted
    $invitation->accept($user->id);
    $invitation->update(['tenant_id' => $tenant->id]);
}
```

**Result:**
- Status: `accepted`
- `accepted_by`: User ID
- `tenant_id`: Tenant ID
- `accepted_at`: Timestamp
- Token no longer valid

---

#### Multi-use Invitations (Without Email/Phone)

**Process:**
1. Keep status as `pending`
2. Don't set `accepted_by`
3. Don't set `tenant_id`
4. Update `updated_at` timestamp
5. Link tenant via `invitation_id`

**Code:**
```php
else {
    // Multi-use invitation: keep pending, don't set accepted_by or tenant_id
    // Owner must manually close it when done
    // Just update timestamp to track last acceptance
    $invitation->touch(); // Update updated_at timestamp
}
```

**Result:**
- Status: `pending` (unchanged)
- `accepted_by`: `NULL`
- `tenant_id`: `NULL`
- `updated_at`: Updated timestamp
- Token still valid (can be used again)
- Tenant linked via `invitation_id`

---

### Step 7: Token Generation

**Location:** `TenantInvitationService::acceptInvitation()`

**Process:**
1. Generate Sanctum access token
2. Generate refresh token
3. Return tokens for immediate login

**Code:**
```php
// Generate tokens for user
$tokens = $user->generateTokens('web');
```

**Tokens Generated:**
- `access_token` - Short-lived (default: 60 minutes)
- `refresh_token` - Long-lived (default: 30 days)
- `token_type` - `Bearer`
- `expires_in` - Expiration time

---

### Step 8: Response

**Location:** `TenantInvitationService::acceptInvitation()`

**Return Data:**
```php
return [
    'user' => $user,
    'tenant' => $tenant,
    'invitation' => $invitation->fresh(['ownership']),
    'tokens' => $tokens,
];
```

**Response Structure:**
```json
{
  "success": true,
  "message": "Registration completed successfully",
  "data": {
    "user": {
      "uuid": "...",
      "email": "...",
      "type": "tenant",
      ...
    },
    "tenant": {
      "id": 1,
      "national_id": "...",
      "ownership": {...},
      ...
    },
    "invitation": {
      "uuid": "...",
      "status": "accepted",
      ...
    },
    "access_token": "...",
    "redirect_to": "/dashboard"
  }
}
```

---

## Data Created/Updated

### New Records Created

1. **User Account** (if new user)
   - `users` table
   - Type: `tenant`
   - Email verified (based on config)

2. **Tenant Profile**
   - `tenants` table
   - Linked to user, ownership, and invitation

3. **Ownership Mapping**
   - `user_ownership_mapping` table
   - Links user to ownership
   - Set as default if first ownership

4. **Role Assignment**
   - `model_has_roles` table (Spatie)
   - Assigns `Tenant` role

5. **Access Token**
   - `personal_access_tokens` table (Sanctum)
   - For immediate login

### Records Updated

1. **User Account** (if existing user)
   - `type` set to `tenant`
   - Role assigned if not already

2. **Invitation** (single-use)
   - `status` → `accepted`
   - `accepted_by` → User ID
   - `tenant_id` → Tenant ID
   - `accepted_at` → Timestamp

3. **Invitation** (multi-use)
   - `updated_at` → Updated timestamp
   - Status remains `pending`

---

## Email Verification

### Configuration

**Config:** `config/auth.php`
```php
'verification' => [
    'enabled' => env('AUTH_EMAIL_VERIFICATION_ENABLED', false),
    'expire' => env('AUTH_VERIFICATION_EXPIRE', 60), // minutes
],
```

### Behavior

**If Enabled:**
- Email verification notification sent
- User must verify email before full access
- `email_verified_at` set after verification

**If Disabled:**
- Email automatically marked as verified
- `email_verified_at` set immediately
- No verification email sent

**Code:**
```php
// In AuthService::register()
if (config('auth.verification.enabled')) {
    $user->sendEmailVerificationNotification();
} else {
    $user->markEmailAsVerified();
}
```

---

## Role Assignment

### Role Lookup

**Role Name:** `Tenant`

**Lookup:**
```php
$tenantRole = Role::where('name', 'Tenant')->first();
```

**Assignment:**
```php
if ($tenantRole) {
    $user->assignRole($tenantRole);
}
```

### Role Permissions

The `Tenant` role should have permissions for:
- Viewing own tenant data
- Updating own profile
- Viewing own contracts
- Viewing own invoices
- Making payments

---

## Ownership Mapping Logic

### Default Ownership

**Rule:** First ownership is set as default

**Logic:**
```php
$userMappings = $this->mappingService->getByUser($user->id);
$isDefault = $userMappings->isEmpty();
```

**If First Ownership:**
- `default` = `true`
- User's default ownership for scope

**If Not First:**
- `default` = `false`
- Previous default remains default

---

## Transaction Safety

### Database Transaction

**All operations wrapped in transaction:**
```php
return DB::transaction(function () use ($token, $registrationData) {
    // All operations here
    // If any fails, all rolled back
});
```

**Benefits:**
- Atomicity: All or nothing
- Data consistency
- Prevents partial registrations

---

## Error Handling

### Validation Errors

**Handled By:**
- `AcceptTenantInvitationRequest` - Form validation
- `TenantInvitationService` - Business logic validation

**Common Errors:**
- Email mismatch (single-use)
- Tenant already exists
- Invalid token
- Expired invitation
- Already accepted (single-use)

### Exception Handling

**In Controller:**
```php
try {
    $result = $this->invitationService->acceptInvitation($token, $request->validated());
    return successResponse(...);
} catch (\Exception $e) {
    return errorResponse($e->getMessage(), 400);
}
```

---

## Related Files

- **Service:** `app/Services/V1/Tenant/TenantInvitationService.php`
- **Auth Service:** `app/Services/V1/Auth/AuthService.php`
- **Mapping Service:** `app/Services/V1/Ownership/UserOwnershipMappingService.php`
- **Controller:** `app/Http/Controllers/Api/V1/Tenant/PublicTenantInvitationController.php`

---

## Related Documentation

- **[Workflow - Tenant](./06-workflow-tenant.md)**
- **[Invitation Types](./07-invitation-types.md)**
- **[Database Schema](./02-database-schema.md)**

