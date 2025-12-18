# Tenant Invitation Notifications - Implementation Summary

## Overview

System notifications have been implemented for the Tenant Invitation feature. Users with the `tenants.invitations.notifications` permission will receive real-time notifications when invitations are created or accepted.

---

## What Was Implemented

### 1. ✅ New Permission

**Permission:** `tenants.invitations.notifications`

**Purpose:** Controls who receives system notifications about tenant invitations.

**Added To:**
- PermissionSeeder (all permissions list)
- Owner role (full access)
- Moderator role (view/manage access)

---

### 2. ✅ Policy Method

**File:** `app/Policies/V1/Tenant/TenantInvitationPolicy.php`

**Method:** `receiveNotifications(User $user, int $ownershipId): bool`

**Logic:**
- Super Admin: Requires `tenants.invitations.notifications` permission
- Regular User: Requires permission AND ownership access

---

### 3. ✅ Notification Service Integration

**File:** `app/Services/V1/Tenant/TenantInvitationService.php`

**Changes:**
- Injected `NotificationService` into constructor
- Added `getUsersToNotify()` helper method
- Added three notification methods:
  - `notifyInvitationCreated()` - When invitation is created
  - `notifyInvitationAccepted()` - When single-use invitation is accepted
  - `notifyTenantJoined()` - When tenant joins via multi-use invitation

---

### 4. ✅ Notification Triggers

#### Trigger 1: Invitation Created

**When:** After invitation is successfully created

**Who Gets Notified:**
- All users with `tenants.invitations.notifications` permission
- Who have access to the invitation's ownership

**Notification Details:**
- Type: `info`
- Title: "New Tenant Invitation Created"
- Message: Includes email, phone, name, ownership, and invited_by
- Action: Link to view invitation

**Location:** `TenantInvitationService::create()` (line 109)

---

#### Trigger 2: Invitation Accepted (Single-use)

**When:** When a tenant accepts a single-use invitation (has email/phone)

**Who Gets Notified:**
- Same as above

**Notification Details:**
- Type: `success`
- Title: "Tenant Invitation Accepted"
- Message: Includes tenant name, email, and ownership
- Action: Link to view tenant profile

**Location:** `TenantInvitationService::acceptInvitation()` (line 328)

---

#### Trigger 3: Tenant Joined (Multi-use)

**When:** When a tenant joins via multi-use invitation (no email/phone)

**Who Gets Notified:**
- Same as above

**Notification Details:**
- Type: `success`
- Title: "New Tenant Joined"
- Message: Includes tenant name, email, ownership, and total tenants count
- Action: Link to view invitation (to see all tenants)

**Location:** `TenantInvitationService::acceptInvitation()` (line 336)

**Note:** This notification is sent **each time** a new tenant joins via the same invitation link.

---

## Notification Flow

### When Invitation is Created

```
Owner creates invitation
  ↓
TenantInvitationService::create()
  ↓
Invitation saved to database
  ↓
Email sent (if email provided)
  ↓
getUsersToNotify(ownership_id)
  ↓
Filter users with permission + ownership access
  ↓
For each user:
  → Create notification via NotificationService
  → Broadcast in real-time (Laravel Broadcasting)
  → User sees notification in UI
```

### When Invitation is Accepted

```
Tenant accepts invitation
  ↓
TenantInvitationService::acceptInvitation()
  ↓
User & Tenant created
  ↓
If single-use:
  → notifyInvitationAccepted()
  → Notification: "Invitation Accepted"
Else (multi-use):
  → notifyTenantJoined()
  → Notification: "New Tenant Joined"
  ↓
getUsersToNotify(ownership_id)
  ↓
Create notifications for all users with permission
```

---

## Permission-Based Notification System

### How It Works

1. **Get Ownership Users**
   - Query `user_ownership_mapping` for ownership
   - Get all user IDs mapped to ownership

2. **Filter by Permission**
   - Check each user has `tenants.invitations.notifications` permission
   - Check user has access to ownership (via `hasOwnership()`)

3. **Send Notifications**
   - Create notification for each eligible user
   - Broadcast via Laravel Broadcasting (Reverb)
   - User receives real-time notification

### Who Gets Notified

**By Default:**
- ✅ Owners (have `tenants.invitations.notifications` permission)
- ✅ Moderators (have `tenants.invitations.notifications` permission)
- ✅ Super Admins (if they have permission and are mapped to ownership)

**Not Notified:**
- ❌ Users without `tenants.invitations.notifications` permission
- ❌ Users without access to the ownership
- ❌ The tenant who registered (they don't need notification about themselves)

---

## Language Files

### English (`lang/en/notifications.php`)

```php
'tenant_invitation' => [
    'created' => [
        'title' => 'New Tenant Invitation Created',
        'message' => 'A new tenant invitation has been created...',
    ],
    'accepted' => [
        'title' => 'Tenant Invitation Accepted',
        'message' => ':tenant_name has accepted the invitation...',
    ],
    'tenant_joined' => [
        'title' => 'New Tenant Joined',
        'message' => ':tenant_name has joined :ownership...',
    ],
]
```

### Arabic (`lang/ar/notifications.php`)

Same structure with Arabic translations.

---

## Database Changes

**No database migrations needed** - Uses existing `notifications` table.

**Notification Structure:**
- `user_id` - User who receives notification
- `type` - `info` or `success`
- `title` - Notification title (localized)
- `message` - Notification message (localized)
- `category` - `tenant_invitation`
- `action_url` - Link to invitation or tenant
- `data` - JSON with invitation/tenant details

---

## Testing

### Test Notification Creation

```bash
# Create invitation
POST /api/v1/tenants/invitations
{
  "email": "tenant@example.com",
  "name": "Test Tenant"
}

# Check notifications for owner
GET /api/v1/notifications?category=tenant_invitation
```

### Test Notification on Acceptance

```bash
# Accept invitation
POST /api/v1/public/tenant-invitations/{token}/accept
{...}

# Check notifications
GET /api/v1/notifications?category=tenant_invitation&type=success
```

---

## Real-Time Broadcasting

Notifications are automatically broadcast via Laravel Broadcasting (Reverb) to:
- **Channel:** `private-user.{user_id}`
- **Event:** `notification.created`

Frontend should listen to this channel to receive real-time notifications.

---

## Files Modified

1. ✅ `database/seeders/V1/Auth/PermissionSeeder.php` - Added permission
2. ✅ `database/seeders/V1/Auth/RoleSeeder.php` - Added to Owner & Moderator
3. ✅ `app/Policies/V1/Tenant/TenantInvitationPolicy.php` - Added method
4. ✅ `app/Services/V1/Tenant/TenantInvitationService.php` - Added notifications
5. ✅ `lang/en/notifications.php` - Added language keys
6. ✅ `lang/ar/notifications.php` - Added language keys

---

## Next Steps

1. **Run Seeders:**
   ```bash
   php artisan db:seed --class=Database\\Seeders\\V1\\Auth\\PermissionSeeder
   php artisan db:seed --class=Database\\Seeders\\V1\\Auth\\RoleSeeder
   ```

2. **Assign Permissions:**
   - Permissions are automatically assigned to Owner and Moderator roles
   - For existing users, permissions will be synced when seeder runs

3. **Test Notifications:**
   - Create an invitation as Owner
   - Check notifications API endpoint
   - Verify real-time broadcasting works

---

## Summary

✅ **Permission-based notification system** - Only users with permission receive notifications  
✅ **Ownership-scoped** - Only users with access to ownership receive notifications  
✅ **Real-time broadcasting** - Notifications sent via Laravel Broadcasting  
✅ **Three notification types** - Created, Accepted, Tenant Joined  
✅ **Multi-use support** - Separate notification for each tenant joining  
✅ **Localized** - English and Arabic support  

