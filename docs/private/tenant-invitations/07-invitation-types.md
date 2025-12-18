# Invitation Types - Single-use vs Multi-use

## Overview

The Tenant Invitation system supports two types of invitations: **Single-use** and **Multi-use**. This document explains the differences, use cases, and implementation details.

---

## Single-use Invitations

### Characteristics

- **Has Email OR Phone:** At least one contact method is provided
- **One-time Use:** Can only be accepted once
- **Status Change:** Changes to `accepted` after first use
- **Email Sent:** Email is automatically sent (if email provided)

### Database Representation

```sql
-- Single-use invitation
INSERT INTO tenant_invitations (
    email, phone, token, status, ...
) VALUES (
    'tenant@example.com', NULL, 'token-abc...', 'pending', ...
);
-- OR
INSERT INTO tenant_invitations (
    email, phone, token, status, ...
) VALUES (
    NULL, '+966501234567', 'token-xyz...', 'pending', ...
);
```

### Behavior

1. **Creation:**
   - Owner provides email OR phone
   - System creates invitation with `email` or `phone` set
   - Email sent automatically (if email provided)
   - Status: `pending`

2. **Acceptance:**
   - Tenant clicks link and registers
   - System validates email matches (if email provided)
   - Creates user and tenant
   - **Status changes to `accepted`**
   - Sets `accepted_by` (user ID)
   - Sets `tenant_id` (first tenant created)
   - **Cannot be used again**

3. **After Acceptance:**
   - Status: `accepted`
   - Token no longer valid
   - Shows who accepted and when
   - Shows tenant created

### Use Cases

✅ **Specific Person Invitation**
- Inviting a known person via email
- Personal invitation
- Track who accepted

✅ **SMS Invitation** (Future)
- Inviting via phone number
- SMS sent with link

✅ **Controlled Access**
- One invitation = one tenant
- Prevents multiple registrations

---

## Multi-use Invitations

### Characteristics

- **No Email AND No Phone:** Both fields are `NULL`
- **Multiple Uses:** Can be accepted by multiple tenants
- **Status Remains:** Stays `pending` until manually closed
- **No Email Sent:** Link must be shared manually

### Database Representation

```sql
-- Multi-use invitation
INSERT INTO tenant_invitations (
    email, phone, token, status, ...
) VALUES (
    NULL, NULL, 'token-public...', 'pending', ...
);
```

### Behavior

1. **Creation:**
   - Owner leaves email AND phone empty
   - System creates invitation with both `NULL`
   - **No email sent**
   - Link generated for manual sharing
   - Status: `pending`

2. **Acceptance (Multiple Times):**
   - First tenant clicks link and registers
   - System creates user and tenant
   - Links tenant to invitation (`invitation_id`)
   - **Status remains `pending`**
   - **No `accepted_by` set**
   - **No `tenant_id` set**
   - Updates `updated_at` timestamp
   
   - Second tenant clicks same link and registers
   - System creates another user and tenant
   - Links to same invitation
   - **Status still `pending`**
   - Process repeats for each tenant

3. **After Multiple Acceptances:**
   - Status: `pending` (unchanged)
   - Multiple tenants linked via `invitation_id`
   - Owner can see all tenants who joined
   - Owner manually closes when done

### Use Cases

✅ **Public Invitation Link**
- Share on website
- Share on social media
- Share in public places

✅ **Open Registration**
- Allow anyone to register
- No need to know email/phone upfront
- Collect registrations over time

✅ **Bulk Onboarding**
- Multiple tenants from same source
- One link for all
- Track all registrations together

---

## Comparison Table

| Feature | Single-use | Multi-use |
|---------|-----------|-----------|
| **Email/Phone** | Required (one or both) | Both NULL |
| **Email Sent** | Yes (if email provided) | No |
| **Max Uses** | 1 | Unlimited |
| **Status After Use** | `accepted` | `pending` |
| **Accepted By** | Set (user ID) | Not set |
| **Tenant ID** | Set (first tenant) | Not set |
| **Tenants Count** | 1 (via `tenant_id`) | Multiple (via `invitation_id`) |
| **Can Close** | Auto-closed after use | Manual close required |
| **Permission to Close** | `tenants.invitations.cancel` | `tenants.invitations.close_without_contact` |
| **Use Case** | Specific person | Public link |

---

## Implementation Details

### Code Logic

#### Single-use Detection

```php
// Check if single-use
if ($invitation->email || $invitation->phone) {
    // Single-use invitation
    // Check if already accepted
    if ($invitation->isAccepted()) {
        throw new Exception('Already accepted');
    }
    // Mark as accepted after use
    $invitation->accept($user->id);
    $invitation->update(['tenant_id' => $tenant->id]);
} else {
    // Multi-use invitation
    // Don't check if accepted
    // Don't mark as accepted
    // Just link tenant to invitation
    $tenant->update(['invitation_id' => $invitation->id]);
    $invitation->touch(); // Update timestamp
}
```

#### Validation Logic

```php
// In PublicTenantInvitationController::validateToken()
if (($invitation->email || $invitation->phone) && $invitation->isAccepted()) {
    return error('Already accepted');
}
// Multi-use invitations don't check isAccepted()
```

---

## API Behavior

### Create Invitation

**Single-use:**
```json
POST /api/v1/tenants/invitations
{
  "email": "tenant@example.com",
  "name": "Ahmed Ali"
}
```
→ Email sent automatically

**Multi-use:**
```json
POST /api/v1/tenants/invitations/generate-link
{
  "email": null,
  "phone": null,
  "expires_in_days": 30
}
```
→ Link generated, no email sent

### Accept Invitation

**Single-use:**
- First acceptance → Status: `accepted`
- Second attempt → Error: "Already accepted"

**Multi-use:**
- First acceptance → Status: `pending`, Tenant 1 created
- Second acceptance → Status: `pending`, Tenant 2 created
- Third acceptance → Status: `pending`, Tenant 3 created
- ... (unlimited)

### View Invitation

**Single-use Response:**
```json
{
  "status": "accepted",
  "accepted_by": { "uuid": "...", "name": "..." },
  "tenant": { "id": 1, "national_id": "..." },
  "tenants_count": null,
  "tenants": null
}
```

**Multi-use Response:**
```json
{
  "status": "pending",
  "accepted_by": null,
  "tenant": null,
  "tenants_count": 3,
  "tenants": [
    { "id": 1, "user": {...}, "national_id": "..." },
    { "id": 2, "user": {...}, "national_id": "..." },
    { "id": 3, "user": {...}, "national_id": "..." }
  ]
}
```

---

## Best Practices

### When to Use Single-use

✅ Inviting specific known person  
✅ Need to track individual acceptance  
✅ Want automatic email delivery  
✅ One invitation = one tenant requirement  

### When to Use Multi-use

✅ Public registration link  
✅ Don't know email/phone upfront  
✅ Multiple tenants from same source  
✅ Open registration period  
✅ Website/social media sharing  

---

## Migration Path

### Converting Single-use to Multi-use

**Not Supported:** Cannot convert single-use to multi-use after creation.

**Workaround:**
1. Cancel single-use invitation
2. Create new multi-use invitation
3. Share new link

### Converting Multi-use to Single-use

**Not Supported:** Cannot convert multi-use to single-use after creation.

**Workaround:**
1. Close multi-use invitation
2. Create single-use invitations for remaining tenants
3. Send individual emails

---

## Related Documentation

- **[Database Schema](./02-database-schema.md)**
- **[Workflow - Owner](./05-workflow-owner.md)**
- **[Workflow - Tenant](./06-workflow-tenant.md)**
- **[Permissions & Security](./09-permissions-security.md)**

