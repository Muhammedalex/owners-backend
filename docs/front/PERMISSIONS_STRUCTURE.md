# Permissions Structure Guide

## Overview

The API returns user permissions in a structured format optimized for frontend UI control. This document explains how permissions are structured and how to use them in your React application.

## API Response Structure

When you call the `/api/v1/auth/me` endpoint, the response includes a `ui.permissions` object:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "...",
    "email": "user@example.com",
    "ui": {
      "permissions": {
        "users": {
          "view": true,
          "create": true,
          "edit": true,
          "delete": false,
          "activate": true,
          "deactivate": true
        },
        "roles": {
          "view": true,
          "create": true,
          "edit": true,
          "delete": true,
          "assign": true
        },
        "invoices": {
          "view": true,
          "create": false,
          "edit": false,
          "delete": false,
          "generate": true
        },
        "portfolios": {
          "view": true,
          "create": false,
          "edit": false,
          "delete": false
        },
        "buildings": {
          "view": true,
          "create": true,
          "edit": true,
          "delete": false
        }
      }
    }
  }
}
```

## Permission Structure

### Format

```typescript
{
  ui: {
    permissions: {
      [resource: string]: {
        [action: string]: boolean
      }
    }
  }
}
```

### Resource Names

Resources are normalized from backend permission names:

- `auth.users.*` → `users`
- `billing.invoices.*` → `invoices`
- `properties.portfolios.*` → `portfolios`
- `properties.buildings.*` → `buildings`
- `properties.units.*` → `units`
- `tenants.*` → `tenants`
- `contracts.*` → `contracts`
- `maintenance.requests.*` → `requests`
- `facilities.*` → `facilities`

### Action Names

Common actions are normalized:

- `view` → `view`
- `create` → `create`
- `update` → `edit`
- `delete` → `delete`
- `activate` → `activate`
- `deactivate` → `deactivate`
- `assign` → `assign`
- `approve` → `approve`
- `sign` → `sign`
- `terminate` → `terminate`
- `verify` → `verify`
- `confirm` → `confirm`
- `generate` → `generate`
- `manage` → `manage`
- `upload` → `upload`
- `send` → `send`

### Common Actions

Most resources include these common actions (set to `false` if not granted):

- `view` - Can view/list items
- `create` - Can create new items
- `edit` - Can update existing items
- `delete` - Can delete items

## Backend Permission Mapping

### Auth Module

| Backend Permission | UI Resource | Actions |
|-------------------|-------------|---------|
| `auth.users.view` | `users` | `view: true` |
| `auth.users.create` | `users` | `create: true` |
| `auth.users.update` | `users` | `edit: true` |
| `auth.users.delete` | `users` | `delete: true` |
| `auth.users.activate` | `users` | `activate: true` |
| `auth.users.deactivate` | `users` | `deactivate: true` |
| `auth.roles.view` | `roles` | `view: true` |
| `auth.roles.create` | `roles` | `create: true` |
| `auth.roles.update` | `roles` | `edit: true` |
| `auth.roles.delete` | `roles` | `delete: true` |
| `auth.roles.assign` | `roles` | `assign: true` |

### Billing Module

| Backend Permission | UI Resource | Actions |
|-------------------|-------------|---------|
| `billing.invoices.view` | `invoices` | `view: true` |
| `billing.invoices.create` | `invoices` | `create: true` |
| `billing.invoices.update` | `invoices` | `edit: true` |
| `billing.invoices.delete` | `invoices` | `delete: true` |
| `billing.invoices.generate` | `invoices` | `generate: true` |
| `billing.payments.view` | `payments` | `view: true` |
| `billing.payments.create` | `payments` | `create: true` |
| `billing.payments.confirm` | `payments` | `confirm: true` |

### Properties Module

| Backend Permission | UI Resource | Actions |
|-------------------|-------------|---------|
| `properties.portfolios.view` | `portfolios` | `view: true` |
| `properties.portfolios.create` | `portfolios` | `create: true` |
| `properties.portfolios.update` | `portfolios` | `edit: true` |
| `properties.portfolios.delete` | `portfolios` | `delete: true` |
| `properties.buildings.view` | `buildings` | `view: true` |
| `properties.buildings.create` | `buildings` | `create: true` |
| `properties.buildings.update` | `buildings` | `edit: true` |
| `properties.buildings.delete` | `buildings` | `delete: true` |
| `properties.units.view` | `units` | `view: true` |
| `properties.units.create` | `units` | `create: true` |
| `properties.units.update` | `units` | `edit: true` |
| `properties.units.delete` | `units` | `delete: true` |

## React Implementation

### 1. TypeScript Types (if using TypeScript)

```typescript
interface UIPermissions {
  [resource: string]: {
    [action: string]: boolean;
  };
}

interface UserResponse {
  id: number;
  uuid: string;
  email: string;
  ui: {
    permissions: UIPermissions;
  };
}
```

### 2. Permission Hook

```javascript
import { useAuth } from '@/features/auth/hooks/useAuth';

export function usePermissions() {
  const { user } = useAuth();
  
  const permissions = user?.ui?.permissions || {};
  
  /**
   * Check if user has a specific permission
   * @param {string} resource - Resource name (e.g., 'users', 'invoices')
   * @param {string} action - Action name (e.g., 'view', 'create', 'edit', 'delete')
   * @returns {boolean}
   */
  const hasPermission = (resource, action) => {
    return permissions[resource]?.[action] === true;
  };
  
  /**
   * Check if user has any of the specified permissions
   * @param {string} resource - Resource name
   * @param {string[]} actions - Array of action names
   * @returns {boolean}
   */
  const hasAnyPermission = (resource, actions) => {
    return actions.some(action => hasPermission(resource, action));
  };
  
  /**
   * Check if user has all of the specified permissions
   * @param {string} resource - Resource name
   * @param {string[]} actions - Array of action names
   * @returns {boolean}
   */
  const hasAllPermissions = (resource, actions) => {
    return actions.every(action => hasPermission(resource, action));
  };
  
  /**
   * Get all permissions for a resource
   * @param {string} resource - Resource name
   * @returns {object} Object with action: boolean pairs
   */
  const getResourcePermissions = (resource) => {
    return permissions[resource] || {};
  };
  
  return {
    permissions,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    getResourcePermissions,
  };
}
```

### 3. Permission Component

```javascript
import { usePermissions } from '@/hooks/usePermissions';

export function PermissionGate({ resource, action, children, fallback = null }) {
  const { hasPermission } = usePermissions();
  
  if (!hasPermission(resource, action)) {
    return fallback;
  }
  
  return children;
}

// Usage:
<PermissionGate resource="users" action="create">
  <button>Create User</button>
</PermissionGate>

<PermissionGate 
  resource="invoices" 
  action="delete"
  fallback={<span>No permission to delete</span>}
>
  <button>Delete Invoice</button>
</PermissionGate>
```

### 4. Permission HOC

```javascript
import { usePermissions } from '@/hooks/usePermissions';

export function withPermission(Component, resource, action) {
  return function PermissionWrappedComponent(props) {
    const { hasPermission } = usePermissions();
    
    if (!hasPermission(resource, action)) {
      return null;
    }
    
    return <Component {...props} />;
  };
}

// Usage:
const CreateUserButton = withPermission(Button, 'users', 'create');
```

### 5. Route Protection

```javascript
import { Navigate } from 'react-router-dom';
import { usePermissions } from '@/hooks/usePermissions';

export function PermissionRoute({ resource, action, children }) {
  const { hasPermission } = usePermissions();
  
  if (!hasPermission(resource, action)) {
    return <Navigate to="/unauthorized" replace />;
  }
  
  return children;
}

// Usage in router:
<Route
  path="/users/create"
  element={
    <PermissionRoute resource="users" action="create">
      <CreateUserPage />
    </PermissionRoute>
  }
/>
```

### 6. Button Disable Based on Permissions

```javascript
import { usePermissions } from '@/hooks/usePermissions';

export function UserActions({ userId }) {
  const { hasPermission } = usePermissions();
  
  return (
    <div>
      <button 
        disabled={!hasPermission('users', 'edit')}
        onClick={() => editUser(userId)}
      >
        Edit
      </button>
      
      <button 
        disabled={!hasPermission('users', 'delete')}
        onClick={() => deleteUser(userId)}
      >
        Delete
      </button>
    </div>
  );
}
```

### 7. Table Column Visibility

```javascript
import { usePermissions } from '@/hooks/usePermissions';

export function UsersTable() {
  const { hasPermission } = usePermissions();
  
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    ...(hasPermission('users', 'edit') ? [{ key: 'actions', label: 'Actions' }] : []),
  ];
  
  return (
    <table>
      <thead>
        <tr>
          {columns.map(col => (
            <th key={col.key}>{col.label}</th>
          ))}
        </tr>
      </thead>
      {/* ... */}
    </table>
  );
}
```

## Best Practices

1. **Always Check Permissions on Frontend**
   - Use permissions to show/hide UI elements
   - Disable buttons/actions user can't perform
   - Hide entire sections if user has no access

2. **Backend Validation is Required**
   - Frontend permissions are for UX only
   - Always validate permissions on backend
   - Never trust frontend permission checks

3. **Cache Permissions**
   - Store permissions in auth store/context
   - Update when user data changes
   - Don't fetch permissions on every check

4. **Use Consistent Resource Names**
   - Match resource names with backend modules
   - Use plural form (users, invoices, etc.)
   - Keep naming consistent across app

5. **Handle Missing Permissions Gracefully**
   - Show appropriate messages
   - Redirect to unauthorized page
   - Don't show error pages for permission issues

## Security Notes

⚠️ **Important:** Frontend permission checks are for **UX purposes only**. They improve user experience by hiding unavailable actions, but they do NOT provide security.

**Security must be enforced on the backend:**
- All API endpoints must check permissions
- Backend validates every request
- Frontend can be bypassed by direct API calls

## Example: Complete User Management Page

```javascript
import { usePermissions } from '@/hooks/usePermissions';
import { PermissionGate } from '@/components/PermissionGate';

export function UsersPage() {
  const { hasPermission, hasAnyPermission } = usePermissions();
  
  const canViewUsers = hasPermission('users', 'view');
  const canManageUsers = hasAnyPermission('users', ['create', 'edit', 'delete']);
  
  if (!canViewUsers) {
    return <Navigate to="/unauthorized" />;
  }
  
  return (
    <div>
      <div className="header">
        <h1>Users</h1>
        <PermissionGate resource="users" action="create">
          <button>Create User</button>
        </PermissionGate>
      </div>
      
      <UsersTable />
    </div>
  );
}
```

## Troubleshooting

### Permissions not showing

1. Check if user is authenticated
2. Verify `/api/v1/auth/me` response includes `ui.permissions`
3. Check browser console for errors
4. Verify permissions are loaded in auth store

### Permission check returns false

1. Verify permission name matches exactly (case-sensitive)
2. Check resource name is correct (plural form)
3. Verify action name is correct (view, create, edit, delete)
4. Check backend user has the permission assigned

### Missing permissions in response

1. Check backend permission seeder ran
2. Verify user role has permissions assigned
3. Check UserResource is transforming permissions correctly
4. Verify PermissionService is working

