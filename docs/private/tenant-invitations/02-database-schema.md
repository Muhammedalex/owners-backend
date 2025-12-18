# Database Schema - Tenant Invitations

## Overview

This document describes the database structure for the Tenant Invitation feature, including tables, relationships, indexes, and migrations.

---

## Tables

### 1. `tenant_invitations`

Stores all tenant invitation records.

#### Schema

```sql
CREATE TABLE tenant_invitations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL,
    ownership_id BIGINT NOT NULL,
    invited_by BIGINT NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    name VARCHAR(255) NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,
    accepted_by BIGINT NULL,
    tenant_id BIGINT NULL,
    notes TEXT NULL,
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
    INDEX idx_expires_at (expires_at),
    INDEX idx_invited_by (invited_by)
);
```

#### Fields

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | BIGINT | NO | Primary key |
| `uuid` | CHAR(36) | NO | Unique identifier (for API) |
| `ownership_id` | BIGINT | NO | Ownership this invitation belongs to |
| `invited_by` | BIGINT | NO | User who created the invitation |
| `email` | VARCHAR(255) | YES | Email address (for single-use invitations) |
| `phone` | VARCHAR(20) | YES | Phone number (for single-use invitations) |
| `name` | VARCHAR(255) | YES | Optional name of invitee |
| `token` | VARCHAR(64) | NO | Secure invitation token (unique) |
| `status` | VARCHAR(50) | NO | Status: `pending`, `accepted`, `expired`, `cancelled` |
| `expires_at` | TIMESTAMP | NO | Expiration date/time |
| `accepted_at` | TIMESTAMP | YES | When invitation was accepted |
| `accepted_by` | BIGINT | YES | User ID who accepted (for single-use) |
| `tenant_id` | BIGINT | YES | First tenant created (for single-use) |
| `notes` | TEXT | YES | Optional notes |
| `created_at` | TIMESTAMP | NO | Creation timestamp |
| `updated_at` | TIMESTAMP | NO | Last update timestamp |

#### Relationships

- **BelongsTo `Ownership`** - The ownership this invitation belongs to
- **BelongsTo `User` (invited_by)** - User who created the invitation
- **BelongsTo `User` (accepted_by)** - User who accepted (single-use only)
- **BelongsTo `Tenant` (tenant_id)** - First tenant created (single-use only)
- **HasMany `Tenant` (via invitation_id)** - All tenants created from this invitation (multi-use)

#### Indexes

- `idx_token` - Fast token lookup (public endpoints)
- `idx_email` - Email search
- `idx_ownership_id` - Ownership filtering
- `idx_status` - Status filtering
- `idx_expires_at` - Expiration queries
- `idx_invited_by` - User filtering

---

### 2. `tenants` (Modified)

Added `invitation_id` field to track which invitation created the tenant.

#### New Field

```sql
ALTER TABLE tenants ADD COLUMN invitation_id BIGINT NULL;
ALTER TABLE tenants ADD FOREIGN KEY (invitation_id) REFERENCES tenant_invitations(id) ON DELETE SET NULL;
ALTER TABLE tenants ADD INDEX idx_invitation_id (invitation_id);
```

#### Field Details

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `invitation_id` | BIGINT | YES | Links tenant to the invitation that created it |

#### Relationships

- **BelongsTo `TenantInvitation`** - The invitation that created this tenant

---

## Migration Files

### 1. Create Tenant Invitations Table

**File:** `database/migrations/2025_12_15_070612_create_tenant_invitations_table.php`

**Purpose:** Creates the `tenant_invitations` table with all fields, indexes, and foreign keys.

**Run:** `php artisan migrate`

### 2. Add Invitation ID to Tenants

**File:** `database/migrations/2025_12_15_080000_add_invitation_id_to_tenants_table.php`

**Purpose:** Adds `invitation_id` foreign key to `tenants` table.

**Run:** `php artisan migrate`

---

## Data Relationships

### Entity Relationship Diagram

```
Ownership (1) ──< (Many) TenantInvitation
    │                      │
    │                      ├──> User (invited_by)
    │                      ├──> User (accepted_by)
    │                      ├──> Tenant (tenant_id) [single-use]
    │                      └──> Tenant[] (via invitation_id) [multi-use]
    │
    └──< (Many) Tenant
            │
            └──> User (user_id)
            └──> TenantInvitation (invitation_id)
```

---

## Data Scenarios

### Scenario 1: Single-use Invitation (With Email)

```sql
-- Invitation created
INSERT INTO tenant_invitations (
    uuid, ownership_id, invited_by, email, token, status, expires_at
) VALUES (
    'uuid-123', 1, 5, 'tenant@example.com', 'token-abc...', 'pending', '2025-12-22 10:00:00'
);

-- Tenant accepts
UPDATE tenant_invitations SET 
    status = 'accepted',
    accepted_at = NOW(),
    accepted_by = 10,
    tenant_id = 1
WHERE id = 1;

INSERT INTO tenants (
    user_id, ownership_id, invitation_id, national_id, ...
) VALUES (
    10, 1, 1, '1234567890', ...
);
```

### Scenario 2: Multi-use Invitation (Without Email/Phone)

```sql
-- Invitation created (no email/phone)
INSERT INTO tenant_invitations (
    uuid, ownership_id, invited_by, token, status, expires_at
) VALUES (
    'uuid-456', 1, 5, 'token-xyz...', 'pending', '2025-12-22 10:00:00'
);

-- First tenant accepts
INSERT INTO tenants (
    user_id, ownership_id, invitation_id, national_id, ...
) VALUES (
    10, 1, 2, '1234567890', ...
);

-- Second tenant accepts (same invitation)
INSERT INTO tenants (
    user_id, ownership_id, invitation_id, national_id, ...
) VALUES (
    11, 1, 2, '0987654321', ...
);

-- Invitation remains 'pending' until owner manually closes it
```

---

## Query Examples

### Find Active Invitations for Ownership

```sql
SELECT * FROM tenant_invitations
WHERE ownership_id = 1
  AND status = 'pending'
  AND expires_at > NOW()
ORDER BY created_at DESC;
```

### Find Invitation by Token

```sql
SELECT * FROM tenant_invitations
WHERE token = 'abc123...'
  AND status = 'pending'
  AND expires_at > NOW();
```

### Get All Tenants from Multi-use Invitation

```sql
SELECT t.*, u.email, u.first, u.last
FROM tenants t
JOIN users u ON t.user_id = u.id
WHERE t.invitation_id = 2;
```

### Count Tenants per Invitation

```sql
SELECT 
    ti.id,
    ti.token,
    ti.status,
    COUNT(t.id) as tenants_count
FROM tenant_invitations ti
LEFT JOIN tenants t ON t.invitation_id = ti.id
GROUP BY ti.id, ti.token, ti.status;
```

---

## Constraints and Rules

### Business Rules

1. **Email/Phone Rule:**
   - If `email` OR `phone` is set → Single-use invitation
   - If both `email` AND `phone` are NULL → Multi-use invitation

2. **Status Rules:**
   - `pending` → Can be accepted
   - `accepted` → Already used (single-use only)
   - `expired` → Past expiration date
   - `cancelled` → Manually cancelled by owner

3. **Expiration Rules:**
   - Default: 7 days from creation
   - Configurable: 1-30 days
   - Cannot accept expired invitations

4. **Token Rules:**
   - Must be unique
   - 64 characters
   - Generated using `Str::random(64)`

---

## Indexes Performance

### Query Performance

- **Token Lookup:** O(log n) - Fast token validation
- **Ownership Filtering:** O(log n) - Efficient ownership scoping
- **Status Filtering:** O(log n) - Quick status queries
- **Expiration Queries:** O(log n) - Efficient expiration checks

### Recommended Indexes

All critical indexes are already created. Additional indexes may be added based on query patterns:

```sql
-- Composite index for common queries
CREATE INDEX idx_ownership_status_expires 
ON tenant_invitations(ownership_id, status, expires_at);

-- Index for email lookups (if needed)
CREATE INDEX idx_email_ownership 
ON tenant_invitations(email, ownership_id);
```

---

## Data Integrity

### Foreign Key Constraints

- **`ownership_id`** → `CASCADE DELETE` - If ownership deleted, invitations deleted
- **`invited_by`** → `CASCADE DELETE` - If user deleted, invitations deleted
- **`accepted_by`** → `SET NULL` - If user deleted, accepted_by set to NULL
- **`tenant_id`** → `SET NULL` - If tenant deleted, tenant_id set to NULL
- **`invitation_id` (in tenants)** → `SET NULL` - If invitation deleted, link removed

### Unique Constraints

- **`uuid`** - Must be unique across all invitations
- **`token`** - Must be unique across all invitations

---

## Migration Rollback

### Rollback Order

1. Rollback `add_invitation_id_to_tenants_table` first
2. Then rollback `create_tenant_invitations_table`

```bash
php artisan migrate:rollback --step=1  # Remove invitation_id from tenants
php artisan migrate:rollback --step=1  # Drop tenant_invitations table
```

---

## Related Files

- **Migration 1:** `database/migrations/2025_12_15_070612_create_tenant_invitations_table.php`
- **Migration 2:** `database/migrations/2025_12_15_080000_add_invitation_id_to_tenants_table.php`
- **Model:** `app/Models/V1/Tenant/TenantInvitation.php`
- **Tenant Model:** `app/Models/V1/Tenant/Tenant.php`

