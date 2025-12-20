# Contract Status Workflow Guide

This document explains the complete contract status workflow, including all possible status transitions, restrictions, and API endpoints.

## Table of Contents

1. [Contract Statuses](#contract-statuses)
2. [Status Transition Rules](#status-transition-rules)
3. [API Endpoints](#api-endpoints)
4. [Status Workflow Scenarios](#status-workflow-scenarios)
5. [System Settings Impact](#system-settings-impact)
6. [Frontend Implementation Guide](#frontend-implementation-guide)

---

## Contract Statuses

### Available Statuses

| Status | Description | Can Edit? | Can Delete? |
|--------|-------------|-----------|-------------|
| **draft** | Contract is being created/edited | ✅ Yes | ✅ Yes |
| **pending** | Contract is waiting for approval | ✅ Yes | ✅ Yes |
| **active** | Contract is active and running | ❌ No | ❌ No |
| **cancelled** | Contract was cancelled before activation | ❌ No | ❌ No |
| **terminated** | Contract was terminated while active | ❌ No | ❌ No |
| **expired** | Contract reached its end date | ❌ No | ❌ No |

---

## Status Transition Rules

### 1. Create Contract (`POST /api/v1/contracts`)

**Important:** Status is **NEVER** accepted from request. The system automatically sets the status based on settings.

#### Default Status Logic:

1. **Get default status from settings** (`default_contract_status`)
2. **Check approval requirement** (`contract_approval_required`)
   - If `contract_approval_required = true` AND default status is `active`:
     - Status is set to `pending` (requires approval)
   - If `contract_approval_required = false`:
     - Status uses the default from settings (can be `active` directly)

#### Example Scenarios:

**Scenario A:** Approval Required = `true`, Default Status = `active`
- **Result:** Contract is created with status `pending`
- **Reason:** Active contracts require approval

**Scenario B:** Approval Required = `false`, Default Status = `active`
- **Result:** Contract is created with status `active`
- **Reason:** No approval needed, can be active immediately

**Scenario C:** Approval Required = `true`, Default Status = `draft`
- **Result:** Contract is created with status `draft`
- **Reason:** Draft doesn't need approval

---

### 2. Update Contract (`PUT/PATCH /api/v1/contracts/{uuid}`)

#### Restrictions:

1. **Active contracts CANNOT be edited** (always blocked)
2. **Status can only be set to `draft` or `pending`** (not `active`)
3. **Cancelled contracts cannot be modified**

#### Allowed Status Transitions in Update:

| Current Status | Can Set To | Notes |
|----------------|------------|-------|
| `draft` | `pending` | Move to approval queue |
| `pending` | `draft` | Revert to draft for editing |
| `active` | ❌ None | Cannot edit active contracts |
| `cancelled` | ❌ None | Cannot modify cancelled contracts |
| `terminated` | ❌ None | Cannot modify terminated contracts |
| `expired` | ❌ None | Cannot modify expired contracts |

#### Status Validation Rules:

- **Cannot set status to `active`** via update (use approve endpoint instead)
- **Cannot cancel via update** (use cancel endpoint instead)
- **Cannot terminate via update** (use terminate endpoint instead)

---

### 3. Approve Contract (`POST /api/v1/contracts/{uuid}/approve`)

#### Purpose:
Moves contract from `pending` or `draft` to `active` status.

#### Requirements:
- Contract status must be `pending` or `draft`
- Cannot approve already `active` contracts
- Cannot approve `cancelled` contracts
- Requires `contracts.approve` permission

#### What Happens:
1. Status changes to `active`
2. Units are marked as `rented`
3. Notifications are sent
4. `approved_by` field is set to current user

---

### 4. Cancel Contract (`POST /api/v1/contracts/{uuid}/cancel`)

#### Purpose:
Cancels a contract that hasn't been activated yet.

#### Requirements:
- Contract status must be `pending` or `draft` only
- Requires `contracts.terminate` permission
- Optional `reason` field (max 500 characters)

#### What Happens:
1. Status changes to `cancelled`
2. Units are released (set to `available`)
3. Notifications are sent

#### Cannot Cancel:
- ❌ Active contracts (use terminate instead)
- ❌ Already cancelled contracts
- ❌ Terminated contracts
- ❌ Expired contracts

---

### 5. Terminate Contract (`POST /api/v1/contracts/{uuid}/terminate`)

#### Purpose:
Terminates an active contract (ends it before expiration).

#### Requirements:
- Contract status must be `active` only
- Requires `contracts.terminate` permission
- Optional `reason` field (max 500 characters)

#### What Happens:
1. Status changes to `terminated`
2. Units are released (set to `available`)
3. Notifications are sent

#### Cannot Terminate:
- ❌ Draft contracts (use cancel instead)
- ❌ Pending contracts (use cancel instead)
- ❌ Already cancelled contracts
- ❌ Already terminated contracts
- ❌ Expired contracts

---

## API Endpoints

### 1. Create Contract

```http
POST /api/v1/contracts
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**Request Body:**
- `status` field is **IGNORED** (not accepted)
- Status is automatically set based on settings

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "...",
    "status": "pending", // or "active" or "draft" based on settings
    ...
  }
}
```

---

### 2. Update Contract

```http
PUT /api/v1/contracts/{uuid}
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**Request Body:**
- `status` field is **OPTIONAL** but can only be `draft` or `pending`
- Cannot set status to `active` (use approve endpoint)
- Cannot edit active contracts

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "...",
    "status": "pending", // or "draft"
    ...
  }
}
```

**Error Cases:**
- `422`: Trying to edit active contract
- `422`: Trying to set status to `active`
- `422`: Trying to set status to `cancelled` or `terminated`

---

### 3. Approve Contract

```http
POST /api/v1/contracts/{uuid}/approve
Authorization: Bearer {token}
```

**Request Body:**
- No body required

**Response:**
```json
{
  "success": true,
  "message": "Contract approved successfully",
  "data": {
    "uuid": "...",
    "status": "active",
    "approved_by": {
      "id": 1,
      "name": "Admin User"
    },
    ...
  }
}
```

**Error Cases:**
- `403`: No permission to approve
- `422`: Contract is already active
- `422`: Contract is cancelled

---

### 4. Cancel Contract

```http
POST /api/v1/contracts/{uuid}/cancel
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "reason": "Optional cancellation reason (max 500 characters)"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Contract cancelled successfully",
  "data": {
    "uuid": "...",
    "status": "cancelled",
    ...
  }
}
```

**Error Cases:**
- `403`: No permission to cancel
- `422`: Contract is not in pending or draft status
- `422`: Contract is already active (use terminate instead)

---

### 5. Terminate Contract

```http
POST /api/v1/contracts/{uuid}/terminate
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "reason": "Optional termination reason (max 500 characters)"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Contract terminated successfully",
  "data": {
    "uuid": "...",
    "status": "terminated",
    ...
  }
}
```

**Error Cases:**
- `403`: No permission to terminate
- `422`: Contract is not active
- `422`: Contract is already cancelled

---

## Status Workflow Scenarios

### Scenario 1: Contract with Approval Required

1. **Create Contract** → Status: `pending` (auto-set)
2. **Update Contract** → Can edit, status can be `draft` or `pending`
3. **Approve Contract** → Status: `active`
4. **After Active** → Cannot edit, cannot cancel (use terminate)

**Flow:**
```
Create → pending → [Update] → pending → Approve → active → Terminate → terminated
                                    ↓
                                 Cancel → cancelled
```

---

### Scenario 2: Contract without Approval Required

1. **Create Contract** → Status: `active` (auto-set if default is active)
2. **After Active** → Cannot edit, cannot cancel (use terminate)

**Flow:**
```
Create → active → Terminate → terminated
```

**Note:** If default status is `draft`, it will be `draft` and can be updated to `pending` or kept as `draft`.

---

### Scenario 3: Draft Contract Workflow

1. **Create Contract** → Status: `draft` (if default is draft)
2. **Update Contract** → Can edit, can change to `pending`
3. **Cancel Contract** → Status: `cancelled` (if not needed)
4. **Approve Contract** → Status: `active` (if ready)

**Flow:**
```
Create → draft → [Update] → draft/pending → Approve → active
                      ↓
                   Cancel → cancelled
```

---

## System Settings Impact

### Key Settings:

1. **`default_contract_status`**
   - Values: `draft`, `pending`, `active`
   - Determines initial status when creating contract

2. **`contract_approval_required`**
   - Values: `true`, `false`
   - If `true` and default is `active`, contract becomes `pending`
   - If `false`, contract can be `active` directly

### Settings Logic Table:

| Approval Required | Default Status | Created Status | Can Be Active? |
|-------------------|----------------|----------------|----------------|
| `true` | `active` | `pending` | Only via approve |
| `true` | `pending` | `pending` | Only via approve |
| `true` | `draft` | `draft` | Only via approve |
| `false` | `active` | `active` | ✅ Yes (immediately) |
| `false` | `pending` | `pending` | ✅ Yes (via update or approve) |
| `false` | `draft` | `draft` | ✅ Yes (via update or approve) |

---

## Frontend Implementation Guide

### 1. Contract Creation Form

**Do NOT include status field:**
```jsx
// ❌ WRONG - Don't include status
<Select name="status" ... />

// ✅ CORRECT - Status is auto-set
// Just create the contract, status will be set automatically
```

**After creation, check the returned status:**
```jsx
const response = await createContract(data);
const contractStatus = response.data.status;

if (contractStatus === 'pending') {
  // Show "Waiting for approval" message
} else if (contractStatus === 'active') {
  // Show "Contract is active" message
}
```

---

### 2. Contract Edit Form

**Check if contract can be edited:**
```jsx
const canEdit = contract.status !== 'active' && 
           contract.status !== 'cancelled' && 
           contract.status !== 'terminated' && 
           contract.status !== 'expired';

if (!canEdit) {
  // Disable edit form or show message
  return <Alert>This contract cannot be edited</Alert>;
}
```

**Status field (if shown):**
```jsx
// Only allow draft or pending
<Select name="status">
  <Option value="draft">Draft</Option>
  <Option value="pending">Pending</Option>
  {/* Don't include active, cancelled, terminated */}
</Select>
```

---

### 3. Action Buttons

**Show appropriate buttons based on status:**
```jsx
const getActionButtons = (contract) => {
  const buttons = [];

  // Edit button
  if (['draft', 'pending'].includes(contract.status)) {
    buttons.push(<Button onClick={handleEdit}>Edit</Button>);
  }

  // Approve button
  if (['draft', 'pending'].includes(contract.status) && hasPermission('contracts.approve')) {
    buttons.push(<Button onClick={handleApprove}>Approve</Button>);
  }

  // Cancel button
  if (['draft', 'pending'].includes(contract.status) && hasPermission('contracts.terminate')) {
    buttons.push(<Button onClick={handleCancel}>Cancel</Button>);
  }

  // Terminate button
  if (contract.status === 'active' && hasPermission('contracts.terminate')) {
    buttons.push(<Button onClick={handleTerminate}>Terminate</Button>);
  }

  return buttons;
};
```

---

### 4. Status Badge Display

```jsx
const getStatusBadge = (status) => {
  const statusConfig = {
    draft: { color: 'gray', label: 'Draft' },
    pending: { color: 'orange', label: 'Pending Approval' },
    active: { color: 'green', label: 'Active' },
    cancelled: { color: 'red', label: 'Cancelled' },
    terminated: { color: 'red', label: 'Terminated' },
    expired: { color: 'gray', label: 'Expired' },
  };

  const config = statusConfig[status] || { color: 'gray', label: status };
  return <Badge color={config.color}>{config.label}</Badge>;
};
```

---

### 5. Error Handling

**Handle common errors:**
```jsx
try {
  await updateContract(contractId, data);
} catch (error) {
  if (error.response?.status === 422) {
    const message = error.response.data.message;
    
    if (message.includes('cannot edit active')) {
      showError('Active contracts cannot be edited');
    } else if (message.includes('cannot set active')) {
      showError('Use the approve endpoint to activate contracts');
    } else if (message.includes('can only cancel pending or draft')) {
      showError('Only pending or draft contracts can be cancelled');
    } else if (message.includes('can only terminate active')) {
      showError('Only active contracts can be terminated');
    }
  }
}
```

---

### 6. Status Transition Flow Diagram

```
┌─────────┐
│  Create │
└────┬────┘
     │
     ▼
┌─────────┐
│  draft  │◄─────┐
└────┬────┘      │
     │           │ Update
     │ Update    │
     ▼           │
┌─────────┐      │
│ pending │──────┘
└────┬────┘
     │
     ├───► Approve ──► ┌─────────┐
     │                 │ active  │
     │                 └────┬────┘
     │                      │
     │                      ├───► Terminate ──► ┌──────────┐
     │                      │                  │terminated│
     │                      │                  └──────────┘
     │                      │
     │                      └───► (Auto) Expire ──► ┌─────────┐
     │                                              │ expired  │
     │                                              └─────────┘
     │
     └───► Cancel ──► ┌──────────┐
                       │cancelled │
                       └──────────┘
```

---

## Summary

### Key Points:

1. ✅ **Status is NEVER accepted in create request** - Always uses default from settings
2. ✅ **Status can only be `draft` or `pending` in update** - Never `active`
3. ✅ **Active contracts cannot be edited** - Use approve/terminate endpoints
4. ✅ **Cancel works on `pending` or `draft` only** - Requires terminate permission
5. ✅ **Terminate works on `active` only** - Requires terminate permission
6. ✅ **Approve moves contract to `active`** - Requires approve permission

### Permission Requirements:

- **Edit:** `contracts.update` permission
- **Approve:** `contracts.approve` permission
- **Cancel/Terminate:** `contracts.terminate` permission

### Best Practices:

1. Always check contract status before showing edit form
2. Show appropriate action buttons based on status
3. Handle errors gracefully with user-friendly messages
4. Don't include status field in create form
5. Only allow `draft`/`pending` in update form status field
6. Use dedicated endpoints for approve/cancel/terminate actions

