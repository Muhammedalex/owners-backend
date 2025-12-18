# Tenant Invitation Permissions

## Overview

This document outlines all permissions required for the Tenant Invitation feature and how they work.

---

## Permissions List

### Standard Permissions

1. **`tenants.invitations.view`**
   - **Purpose:** View tenant invitations
   - **Used in:** `TenantInvitationPolicy::viewAny()`, `TenantInvitationPolicy::view()`
   - **Access:** List and view individual invitations

2. **`tenants.invitations.create`**
   - **Purpose:** Create tenant invitations
   - **Used in:** `TenantInvitationPolicy::create()`
   - **Access:** Create single invitation, bulk invitations, generate links

3. **`tenants.invitations.update`**
   - **Purpose:** Update tenant invitations
   - **Used in:** `TenantInvitationPolicy::update()`
   - **Access:** Modify invitation details

4. **`tenants.invitations.delete`**
   - **Purpose:** Delete tenant invitations
   - **Used in:** `TenantInvitationPolicy::delete()`
   - **Access:** Remove invitations

5. **`tenants.invitations.cancel`**
   - **Purpose:** Cancel tenant invitations (with email/phone)
   - **Used in:** `TenantInvitationPolicy::cancel()`
   - **Access:** Cancel pending invitations that have contact info

6. **`tenants.invitations.resend`**
   - **Purpose:** Resend invitation emails
   - **Used in:** `TenantInvitationPolicy::resend()`
   - **Access:** Resend invitation emails to recipients

### Special Permission

7. **`tenants.invitations.close_without_contact`**
   - **Purpose:** Close/cancel invitations without email/phone
   - **Used in:** `TenantInvitationPolicy::closeWithoutContact()`
   - **Access:** Cancel invitations that have no email or phone
   - **Special:** Only applies to invitations without contact information
   - **Note:** These invitations cannot be accepted through self-registration

---

## Permission Logic

### Standard Invitations (With Email/Phone)

- **Create:** Requires `tenants.invitations.create`
- **View:** Requires `tenants.invitations.view`
- **Cancel:** Requires `tenants.invitations.cancel`
- **Resend:** Requires `tenants.invitations.resend`
- **Delete:** Requires `tenants.invitations.delete`

### Invitations Without Email/Phone

- **Create:** Requires `tenants.invitations.create`
- **View:** Requires `tenants.invitations.view`
- **Cancel/Close:** Requires `tenants.invitations.close_without_contact` (special permission)
- **Cannot be accepted:** These invitations cannot be accepted through self-registration
- **Cannot be resent:** No email/phone to send to

---

## Policy Behavior

### Ownership Scope

All permissions check:
1. **Super Admin:** Can perform action if they have the permission
2. **Regular User:** Must have permission AND access to the invitation's ownership

### Invitation Acceptance

- **With Email/Phone:** User can self-register, `accepted_by` is set to user ID
- **Without Email/Phone:** Cannot be accepted, can only be closed manually, `accepted_by` is NULL

---

## Permission Setup Example

### Role: Owner
```php
$ownerRole->givePermissionTo([
    'tenants.invitations.view',
    'tenants.invitations.create',
    'tenants.invitations.cancel',
    'tenants.invitations.resend',
]);
```

### Role: Manager (Can Close Without Contact)
```php
$managerRole->givePermissionTo([
    'tenants.invitations.view',
    'tenants.invitations.create',
    'tenants.invitations.cancel',
    'tenants.invitations.resend',
    'tenants.invitations.close_without_contact', // Special permission
]);
```

### Role: Admin (Full Access)
```php
$adminRole->givePermissionTo([
    'tenants.invitations.view',
    'tenants.invitations.create',
    'tenants.invitations.update',
    'tenants.invitations.delete',
    'tenants.invitations.cancel',
    'tenants.invitations.resend',
    'tenants.invitations.close_without_contact',
]);
```

---

## API Endpoints & Permissions

| Endpoint | Method | Permission Required |
|----------|--------|---------------------|
| `/api/v1/tenants/invitations` | GET | `tenants.invitations.view` |
| `/api/v1/tenants/invitations` | POST | `tenants.invitations.create` |
| `/api/v1/tenants/invitations/bulk` | POST | `tenants.invitations.create` |
| `/api/v1/tenants/invitations/generate-link` | POST | `tenants.invitations.create` |
| `/api/v1/tenants/invitations/{uuid}` | GET | `tenants.invitations.view` |
| `/api/v1/tenants/invitations/{uuid}/resend` | POST | `tenants.invitations.resend` |
| `/api/v1/tenants/invitations/{uuid}/cancel` | POST | `tenants.invitations.cancel` OR `tenants.invitations.close_without_contact`* |

*`close_without_contact` is required only if invitation has no email/phone

---

## Database Behavior

### Invitations With Email/Phone
- User self-registers → `accepted_by` = user ID
- `status` = 'accepted'
- `accepted_at` = timestamp

### Invitations Without Email/Phone
- Cannot be accepted through self-registration
- Can only be closed manually → `accepted_by` = NULL
- `status` = 'cancelled'
- Requires `tenants.invitations.close_without_contact` permission

---

## Error Messages

- **No Permission:** Standard Laravel 403 Forbidden
- **Cannot Accept Without Contact:** "This invitation cannot be accepted through self-registration. Please contact the owner."
- **Already Accepted:** "Invitation has already been accepted"
- **Expired:** "Invitation has expired"
- **Cancelled:** "Invitation has been cancelled"

---

## Notes

1. **Super Admins:** Have access to all invitations regardless of ownership (if they have permission)
2. **Ownership Scope:** Regular users can only access invitations for ownerships they have access to
3. **Contact Info Required:** Invitations without email/phone are for manual processing only
4. **Special Permission:** `close_without_contact` is only checked when cancelling invitations without contact info

