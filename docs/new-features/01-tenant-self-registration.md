# Feature 1: Tenant Self-Registration via Invitation Link

## Overview

Allow tenants to register themselves using a secure invitation link sent by the ownership owner. This feature eliminates manual tenant creation and streamlines the onboarding process.

---

## Business Requirements

### User Stories

1. **As an Owner**, I want to send invitation links to potential tenants so they can register themselves.
2. **As a Tenant**, I want to receive an invitation link via email and complete my registration independently.
3. **As a System**, I want to ensure invitation links are secure, time-limited, and ownership-scoped.

### Key Requirements

- ✅ Owner can generate invitation links for tenants
- ✅ Invitation links sent via email
- ✅ Links are secure (token-based, time-limited)
- ✅ Tenant completes registration form independently
- ✅ Automatic user account creation upon registration
- ✅ Automatic tenant profile creation linked to ownership
- ✅ Invitation tracking and status management

---

## Workflow

### 1. Owner Generates Invitation

```
Owner → Dashboard → Tenants → "Invite Tenant"
  ↓
Enter tenant email (optional: name, phone)
  ↓
System generates secure token
  ↓
Invitation record created (status: pending)
  ↓
Email sent with registration link
  ↓
Owner sees invitation status
```

### 2. Tenant Receives & Registers

```
Tenant receives email
  ↓
Clicks registration link
  ↓
Redirected to registration page
  ↓
Completes registration form:
  - Personal info (name, email, phone)
  - Password
  - National ID
  - ID document upload
  - Emergency contact
  - Employment info
  ↓
Submits form
  ↓
System validates data
  ↓
Creates User account
  ↓
Creates Tenant profile (linked to ownership)
  ↓
Marks invitation as accepted
  ↓
Sends welcome email
  ↓
Redirects to login page
```

### 3. Invitation Management

```
Owner can:
  - View all invitations (pending, accepted, expired, cancelled)
  - Resend invitation email
  - Cancel pending invitation
  - View invitation details
```

---

## Database Design

### New Table: `tenant_invitations`

```sql
CREATE TABLE tenant_invitations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL,
    ownership_id BIGINT NOT NULL,
    invited_by BIGINT NOT NULL, -- User who sent invitation
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NULLABLE, -- Optional pre-filled name
    phone VARCHAR(20) NULLABLE, -- Optional pre-filled phone
    token VARCHAR(64) UNIQUE NOT NULL, -- Secure invitation token
    status VARCHAR(50) DEFAULT 'pending', -- pending, accepted, expired, cancelled
    expires_at TIMESTAMP NOT NULL, -- Default: 7 days from creation
    accepted_at TIMESTAMP NULLABLE,
    accepted_by BIGINT NULLABLE, -- User ID who accepted (if user exists)
    tenant_id BIGINT NULLABLE, -- Created tenant ID
    notes TEXT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (ownership_id) REFERENCES ownerships(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (accepted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_ownership_id (ownership_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
);
```

### Migration File

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_tenant_invitations_table.php`

---

## Models

### TenantInvitation Model

**File:** `app/Models/V1/Tenant/TenantInvitation.php`

```php
<?php

namespace App\Models\V1\Tenant;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Traits\V1\Auth\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantInvitation extends Model
{
    use HasUuid;

    protected $table = 'tenant_invitations';

    protected $fillable = [
        'uuid',
        'ownership_id',
        'invited_by',
        'email',
        'name',
        'phone',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'tenant_id',
        'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    // Relationships
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    // Helper Methods
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
```

---

## Services

### TenantInvitationService

**File:** `app/Services/V1/Tenant/TenantInvitationService.php`

**Key Methods:**
- `create(array $data): TenantInvitation` - Create invitation
- `findByToken(string $token): ?TenantInvitation` - Find by token
- `acceptInvitation(string $token, array $registrationData): Tenant` - Accept and create tenant
- `resendInvitation(TenantInvitation $invitation): bool` - Resend email
- `cancelInvitation(TenantInvitation $invitation): bool` - Cancel invitation
- `expireOldInvitations(): int` - Mark expired invitations (scheduled job)

---

## API Endpoints

### Owner Endpoints (Authenticated, Ownership Scoped)

```
POST   /api/v1/tenants/invitations
GET    /api/v1/tenants/invitations
GET    /api/v1/tenants/invitations/{uuid}
POST   /api/v1/tenants/invitations/{uuid}/resend
POST   /api/v1/tenants/invitations/{uuid}/cancel
```

### Public Endpoint (No Authentication)

```
GET    /api/v1/public/tenant-invitations/{token}        # Validate token
POST   /api/v1/public/tenant-invitations/{token}/accept  # Accept invitation & register
```

---

## Request/Response Examples

### Create Invitation Request

**POST** `/api/v1/tenants/invitations`

```json
{
  "email": "tenant@example.com",
  "name": "Ahmed Ali",  // Optional
  "phone": "+966501234567",  // Optional
  "expires_in_days": 7,  // Optional, default: 7
  "notes": "Invitation for new office tenant"  // Optional
}
```

**Response:**

```json
{
  "success": true,
  "message": "Invitation sent successfully",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "email": "tenant@example.com",
    "name": "Ahmed Ali",
    "status": "pending",
    "expires_at": "2025-12-20T10:00:00Z",
    "invitation_url": "https://app.example.com/register/tenant?token=abc123..."
  }
}
```

### Accept Invitation Request

**POST** `/api/v1/public/tenant-invitations/{token}/accept`

```json
{
  "first_name": "Ahmed",
  "last_name": "Ali",
  "email": "tenant@example.com",  // Must match invitation email
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

**Response:**

```json
{
  "success": true,
  "message": "Registration completed successfully",
  "data": {
    "user": {
      "uuid": "user-uuid",
      "email": "tenant@example.com",
      "first": "Ahmed",
      "last": "Ali"
    },
    "tenant": {
      "id": 1,
      "national_id": "1234567890",
      "ownership": {
        "uuid": "ownership-uuid",
        "name": "ABC Real Estate"
      }
    },
    "access_token": "sanctum-token",
    "redirect_to": "/dashboard"
  }
}
```

---

## Email Templates

### Invitation Email

**Subject:** `You're invited to register as a tenant - {Ownership Name}`

**Template:** `resources/views/emails/tenant-invitation.blade.php`

**Content:**
```
Dear {Name or "Future Tenant"},

You have been invited by {Ownership Name} to register as a tenant in their property management system.

Click the link below to complete your registration:
{Registration Link}

This link will expire on {Expiration Date}.

If you did not expect this invitation, please ignore this email.

Best regards,
{Ownership Name}
```

### Welcome Email (After Registration)

**Subject:** `Welcome to {Ownership Name} - Registration Complete`

**Template:** `resources/views/emails/tenant-welcome.blade.php`

---

## Security Considerations

1. **Token Generation:**
   - Use `Str::random(64)` or `hash_hmac('sha256', $data, $secret)`
   - Store hashed token in database
   - Compare using `Hash::check()`

2. **Token Expiration:**
   - Default: 7 days
   - Configurable per ownership
   - Automatic expiration check

3. **Email Validation:**
   - Email must match invitation email
   - Prevent duplicate registrations

4. **Rate Limiting:**
   - Limit invitation creation per owner
   - Limit registration attempts per token

5. **CSRF Protection:**
   - Public endpoints use token-based validation
   - No session required for public registration

---

## Permissions

### New Permissions

```
tenants.invitations.create
tenants.invitations.view
tenants.invitations.resend
tenants.invitations.cancel
```

---

## Scheduled Jobs

### Expire Old Invitations

**File:** `app/Console/Commands/ExpireTenantInvitations.php`

**Schedule:** Daily at 2:00 AM

```php
// app/Console/Kernel.php
$schedule->command('invitations:expire')->dailyAt('02:00');
```

---

## Frontend Considerations

### Owner Dashboard

- **Tenants Page:** Add "Invite Tenant" button
- **Invitations Tab:** List all invitations with status
- **Actions:** Resend, Cancel, View Details

### Public Registration Page

- **Route:** `/register/tenant?token={token}`
- **Validate token** on page load
- **Show expiration warning** if expiring soon
- **Form fields:** All tenant registration fields
- **Success:** Redirect to login with success message

---

## Testing Scenarios

1. ✅ Owner creates invitation successfully
2. ✅ Email sent with correct link
3. ✅ Token validation works
4. ✅ Registration form pre-filled with invitation data
5. ✅ Registration creates user and tenant
6. ✅ Invitation marked as accepted
7. ✅ Expired invitations cannot be used
8. ✅ Cancelled invitations cannot be used
9. ✅ Duplicate email registration prevented
10. ✅ Resend invitation works
11. ✅ Cancel invitation works

---

## Implementation Checklist

- [ ] Create migration for `tenant_invitations` table
- [ ] Create `TenantInvitation` model
- [ ] Create `TenantInvitationService`
- [ ] Create `TenantInvitationRepository`
- [ ] Create `TenantInvitationController` (owner endpoints)
- [ ] Create `PublicTenantInvitationController` (public endpoints)
- [ ] Create request validation classes
- [ ] Create email templates (invitation, welcome)
- [ ] Create notification events
- [ ] Add permissions to seeder
- [ ] Create scheduled job for expiration
- [ ] Write tests
- [ ] Update API documentation
- [ ] Frontend integration

---

## Future Enhancements

1. **Bulk Invitations:** Send multiple invitations at once
2. **Custom Expiration:** Per-invitation expiration settings
3. **Invitation Templates:** Customizable email templates per ownership
4. **SMS Invitations:** Send invitation via SMS (when SMS service ready)
5. **QR Code:** Generate QR code for invitation link
6. **Analytics:** Track invitation acceptance rates

