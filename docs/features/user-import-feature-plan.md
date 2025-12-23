# User Import Feature Plan

## Overview
Allow users with appropriate permissions to import users from one ownership to another ownership (both ownerships must be mapped to the current user).

## Business Requirements

### 1. Permission Requirements
- User must have one of these permissions:
  - `auth.users.view` - Can view all users across mapped ownerships
  - `ownerships.users.assign` - Can assign users to ownerships
- User must be mapped to both:
  - **Source Ownership** (where users are imported from)
  - **Target Ownership** (current ownership where users are imported to)

### 2. Functionality
- **Import Users**: Copy user mappings from source ownership to target ownership
- **Multi-Select**: Allow selecting multiple users at once
- **Duplicate Prevention**: Skip users already mapped to target ownership
- **Tenant Auto-Creation**: If imported user is a tenant (has Tenant role or type='tenant'), automatically create tenant record in target ownership

### 3. User Flow
1. User clicks "Import Users" button in Users List page
2. Modal opens with:
   - **Source Ownership Select**: Dropdown showing all ownerships user is mapped to (excluding current ownership)
   - **Users Multi-Select**: Searchable multi-select showing users from selected source ownership
   - **Filter**: Show only users not already mapped to current ownership
   - **Import Button**: Execute import
3. After import:
   - Show success message with count of imported users
   - Show list of skipped users (already exist)
   - Refresh users list

## Technical Implementation

### Backend

#### 1. New API Endpoint
**Route**: `POST /api/v1/users/import`
**Controller**: `UserController@import`
**Request**: `ImportUsersRequest`

**Request Body**:
```json
{
  "source_ownership_id": 1,
  "user_ids": [1, 2, 3],
  "create_tenant_if_needed": true
}
```

**Response**:
```json
{
  "success": true,
  "message": "Successfully imported 2 users. 1 user(s) skipped (already exist).",
  "data": {
    "imported": 2,
    "skipped": 1,
    "tenants_created": 1,
    "imported_users": [...],
    "skipped_users": [...]
  }
}
```

#### 2. Service Layer
**File**: `app/Services/V1/Auth/UserImportService.php`

**Methods**:
- `importUsers(int $sourceOwnershipId, array $userIds, int $targetOwnershipId, User $currentUser): array`
  - Validate source and target ownership access
  - Filter out users already mapped to target ownership
  - Create user ownership mappings
  - Create tenant records if needed
  - Return import results

#### 3. Request Validation
**File**: `app/Http/Requests/V1/Auth/ImportUsersRequest.php`

**Rules**:
- `source_ownership_id`: required, integer, exists:ownerships,id
- `user_ids`: required, array, min:1
- `user_ids.*`: integer, exists:users,id
- `create_tenant_if_needed`: boolean, default:true

**Authorization**:
- Check `auth.users.view` or `ownerships.users.assign` permission
- Verify user has access to source ownership
- Verify user has access to target ownership (current ownership)

#### 4. Policy Updates
**File**: `app/Policies/V1/Auth/UserPolicy.php`

Add method:
- `import(User $user): bool` - Check if user can import users

#### 5. Repository Updates
**File**: `app/Repositories/V1/Auth/UserRepository.php`

Add methods:
- `getUsersByOwnership(int $ownershipId, array $excludeUserIds = []): Collection`
  - Get users mapped to specific ownership
  - Exclude users already in target ownership

### Frontend

#### 1. New Component
**File**: `israa/src/components/users/ImportUsersModal/ImportUsersModal.jsx`

**Props**:
- `open`: boolean
- `onClose`: function
- `onSuccess`: function (callback after successful import)

**State**:
- `sourceOwnershipId`: selected source ownership
- `selectedUserIds`: array of selected user IDs
- `loading`: boolean
- `error`: string

**Features**:
- Ownership selector (dropdown)
- Users multi-select with search
- Filter to exclude already-mapped users
- Loading states
- Error handling
- Success feedback

#### 2. API Integration
**File**: `israa/src/api/v1/users/users.api.js`

Add function:
```javascript
export async function importUsers(data) {
  return apiClient.post('/users/import', data);
}
```

**File**: `israa/src/api/v1/users/queries.js`

Add hook:
```javascript
export function useImportUsers() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (data) => usersApi.importUsers(data),
    onSuccess: () => {
      queryClient.invalidateQueries(['users']);
    },
  });
}
```

#### 3. Users List Page Integration
**File**: `israa/src/pages/users/UsersListPage.jsx`

Add:
- "Import Users" button (shown if user has permission)
- Import modal integration
- Refresh after successful import

#### 4. Translations
**File**: `israa/src/i18n/locales/en.json` & `ar.json`

Add keys:
```json
{
  "users": {
    "import": {
      "title": "Import Users",
      "description": "Import users from another ownership",
      "sourceOwnership": "Source Ownership",
      "selectSourceOwnership": "Select source ownership",
      "selectUsers": "Select Users",
      "searchUsers": "Search users...",
      "noUsersFound": "No users found",
      "usersAlreadyMapped": "These users are already mapped to this ownership",
      "importButton": "Import Selected Users",
      "importing": "Importing...",
      "success": {
        "title": "Import Successful",
        "imported": "{{count}} user(s) imported successfully",
        "skipped": "{{count}} user(s) skipped (already exist)",
        "tenantsCreated": "{{count}} tenant record(s) created"
      },
      "errors": {
        "sourceOwnershipRequired": "Please select source ownership",
        "usersRequired": "Please select at least one user",
        "importFailed": "Failed to import users"
      }
    }
  }
}
```

### Database Considerations

#### 1. User Ownership Mapping
- Table: `user_ownership_mapping`
- Unique constraint: `(user_id, ownership_id)` - prevents duplicates
- No changes needed

#### 2. Tenant Records
- Table: `tenants`
- Unique constraint: `user_id` (one tenant record per user globally)
- **Issue**: Current migration has `user_id` as unique, but tenant should be per ownership
- **Solution**: Need to check if tenant already exists for user+ownership combination
- If exists, skip tenant creation
- If not exists and user is tenant, create tenant record

**Note**: Check migration - if `user_id` is unique globally, we may need to update the logic to allow multiple tenant records per user (one per ownership).

## Security & Validation

### 1. Authorization Checks
- ✅ User has `auth.users.view` or `ownerships.users.assign` permission
- ✅ User is mapped to source ownership
- ✅ User is mapped to target ownership (current ownership)
- ✅ Source ownership is not the same as target ownership

### 2. Data Validation
- ✅ Source ownership exists
- ✅ All user IDs exist
- ✅ Users belong to source ownership
- ✅ Skip users already mapped to target ownership

### 3. Business Rules
- ✅ Cannot import users to same ownership they're already in
- ✅ If user is tenant, create tenant record automatically
- ✅ Preserve user roles (they're global, not ownership-specific)
- ✅ User ownership mapping is created with `default=false` (unless it's their first ownership)

## Edge Cases

1. **User already mapped**: Skip silently, return in skipped list
2. **User is tenant in source but not in target**: Create tenant record
3. **User is tenant in both**: Skip tenant creation (already exists)
4. **Source ownership has no users**: Show empty state
5. **All selected users already mapped**: Show message, don't create any mappings
6. **Partial import failure**: Use transaction, rollback all if any fails

## Testing Scenarios

### Unit Tests
1. ✅ User with permission can import
2. ✅ User without permission cannot import
3. ✅ Cannot import from ownership user is not mapped to
4. ✅ Cannot import to ownership user is not mapped to
5. ✅ Duplicate users are skipped
6. ✅ Tenant records are created for tenant users
7. ✅ Non-tenant users don't get tenant records

### Integration Tests
1. ✅ Full import flow with multiple users
2. ✅ Import with some duplicates
3. ✅ Import with tenant users
4. ✅ Error handling for invalid ownership
5. ✅ Error handling for invalid user IDs

## Implementation Steps

### Phase 1: Backend Foundation
1. ✅ Create `UserImportService`
2. ✅ Create `ImportUsersRequest`
3. ✅ Add `import` method to `UserController`
4. ✅ Add route
5. ✅ Update `UserPolicy`
6. ✅ Add repository methods if needed

### Phase 2: Frontend Components
1. ✅ Create `ImportUsersModal` component
2. ✅ Add API functions
3. ✅ Add React Query hooks
4. ✅ Integrate into Users List page
5. ✅ Add translations

### Phase 3: Testing & Refinement
1. ✅ Unit tests
2. ✅ Integration tests
3. ✅ UI/UX improvements
4. ✅ Error handling
5. ✅ Documentation

## Notes

- **Tenant Record Issue**: Current `tenants` table has `user_id` as unique, meaning one tenant record per user globally. However, tenant should be per ownership. Need to verify if this is correct or if we need to allow multiple tenant records per user (one per ownership).

- **User Roles**: User roles are global (Spatie Permission), not ownership-specific. When importing, roles are preserved automatically.

- **Default Ownership**: When creating new mapping, set `default=false` unless it's the user's first ownership mapping.

- **Performance**: For large user lists, consider pagination or virtual scrolling in the multi-select component.

