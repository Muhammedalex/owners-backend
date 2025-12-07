# Permission System - Implementation Guide

## Overview

Complete guide for implementing the permission-based access control system in the React frontend.

---

## Permission Philosophy

### Core Principles

1. **Permissions are Source of Truth** - All access control based on permissions
2. **Roles are Organizational** - Roles are just permission groups
3. **Client-Side is UX Only** - Server validates all permissions
4. **Progressive Enhancement** - Hide UI, but server enforces

---

## Permission Structure

### Permission Format

```
{module}.{resource}.{action}
```

### Examples

- `auth.users.view` - View users list
- `auth.users.create` - Create users
- `auth.users.update` - Update users
- `auth.users.delete` - Delete users
- `ownerships.view` - View ownerships
- `properties.units.create` - Create units

---

## Implementation Components

### 1. Permission Store

**Purpose:** Centralized permission state management

**Implementation:**
```javascript
// features/auth/store/permission.store.js
import { create } from 'zustand';

export const usePermissionStore = create((set, get) => ({
  permissions: [],
  roles: [],
  isLoaded: false,
  
  setPermissions: (permissions) => set({ permissions, isLoaded: true }),
  setRoles: (roles) => set({ roles }),
  
  hasPermission: (permission) => {
    const { permissions } = get();
    return permissions.includes(permission);
  },
  
  hasAnyPermission: (permissions) => {
    const { permissions: userPermissions } = get();
    return permissions.some(p => userPermissions.includes(p));
  },
  
  hasAllPermissions: (permissions) => {
    const { permissions: userPermissions } = get();
    return permissions.every(p => userPermissions.includes(p));
  },
  
  hasRole: (role) => {
    const { roles } = get();
    return roles.includes(role);
  },
  
  clearPermissions: () => set({ 
    permissions: [], 
    roles: [], 
    isLoaded: false 
  }),
}));
```

---

### 2. Permission Hooks

#### usePermissions Hook

```javascript
// features/auth/hooks/usePermissions.js
export const usePermissions = () => {
  const store = usePermissionStore();
  
  return {
    permissions: store.permissions,
    roles: store.roles,
    isLoaded: store.isLoaded,
    hasPermission: store.hasPermission,
    hasAnyPermission: store.hasAnyPermission,
    hasAllPermissions: store.hasAllPermissions,
    hasRole: store.hasRole,
  };
};
```

#### useHasPermission Hook

```javascript
// features/auth/hooks/useHasPermission.js
export const useHasPermission = (permission) => {
  const { hasPermission, isLoaded } = usePermissions();
  
  return {
    hasPermission: hasPermission(permission),
    isLoading: !isLoaded,
  };
};
```

---

### 3. Permission Components

#### PermissionGuard Component

```javascript
// features/auth/components/PermissionGuard.jsx
export const PermissionGuard = ({
  permission,
  requireAll = false,
  fallback = null,
  children,
}) => {
  const { hasPermission, hasAnyPermission, hasAllPermissions } = usePermissions();
  
  let hasAccess = false;
  
  if (Array.isArray(permission)) {
    hasAccess = requireAll 
      ? hasAllPermissions(permission)
      : hasAnyPermission(permission);
  } else {
    hasAccess = hasPermission(permission);
  }
  
  return hasAccess ? <>{children}</> : <>{fallback}</>;
};
```

#### RoleGuard Component

```javascript
// features/auth/components/RoleGuard.jsx
export const RoleGuard = ({
  role,
  requireAll = false,
  fallback = null,
  children,
}) => {
  const { hasRole, roles } = usePermissions();
  
  let hasAccess = false;
  
  if (Array.isArray(role)) {
    hasAccess = requireAll
      ? role.every(r => roles.includes(r))
      : role.some(r => roles.includes(r));
  } else {
    hasAccess = hasRole(role);
  }
  
  return hasAccess ? <>{children}</> : <>{fallback}</>;
};
```

---

### 4. Permission Route Guards

#### PermissionRoute Component

```javascript
// features/auth/guards/PermissionRoute.jsx
export const PermissionRoute = ({
  permission,
  requireAll = false,
  redirectTo = '/dashboard',
  children,
}) => {
  const { hasPermission, hasAnyPermission, hasAllPermissions, isLoaded } = usePermissions();
  const navigate = useNavigate();
  
  useEffect(() => {
    if (!isLoaded) return;
    
    let hasAccess = false;
    
    if (Array.isArray(permission)) {
      hasAccess = requireAll 
        ? hasAllPermissions(permission)
        : hasAnyPermission(permission);
    } else {
      hasAccess = hasPermission(permission);
    }
    
    if (!hasAccess) {
      navigate(redirectTo);
      // Show access denied message
    }
  }, [permission, isLoaded, navigate, redirectTo]);
  
  if (!isLoaded) {
    return <Loading />;
  }
  
  let hasAccess = false;
  if (Array.isArray(permission)) {
    hasAccess = requireAll 
      ? hasAllPermissions(permission)
      : hasAnyPermission(permission);
  } else {
    hasAccess = hasPermission(permission);
  }
  
  return hasAccess ? <>{children}</> : <AccessDenied />;
};
```

---

## Usage Examples

### 1. Conditional Rendering

```jsx
// Using hook
const { hasPermission } = usePermissions();

return (
  <div>
    {hasPermission('auth.users.view') && (
      <Link to="/users">Users</Link>
    )}
    
    {hasPermission('auth.users.create') && (
      <Button onClick={handleCreate}>Create User</Button>
    )}
  </div>
);
```

### 2. Using PermissionGuard

```jsx
<PermissionGuard 
  permission="auth.users.create"
  fallback={<div>You don't have permission to create users</div>}
>
  <CreateUserForm />
</PermissionGuard>
```

### 3. Multiple Permissions

```jsx
// Any permission
<PermissionGuard 
  permission={['auth.users.view', 'auth.users.create']}
  requireAll={false}
>
  <UsersSection />
</PermissionGuard>

// All permissions
<PermissionGuard 
  permission={['auth.users.view', 'auth.users.create']}
  requireAll={true}
>
  <FullUsersSection />
</PermissionGuard>
```

### 4. Route Protection

```jsx
<Route
  path="/users"
  element={
    <PermissionRoute permission="auth.users.view">
      <UsersPage />
    </PermissionRoute>
  }
/>

<Route
  path="/admin"
  element={
    <RoleRoute role="Super Admin">
      <AdminPage />
    </RoleRoute>
  }
/>
```

### 5. Menu Items

```jsx
const menuItems = [
  {
    label: 'Dashboard',
    path: '/dashboard',
    icon: <DashboardIcon />,
  },
  {
    label: 'Users',
    path: '/users',
    icon: <UsersIcon />,
    permission: 'auth.users.view', // Only show if has permission
  },
  {
    label: 'Roles',
    path: '/roles',
    icon: <RolesIcon />,
    permission: 'auth.roles.view',
  },
];

// Filter menu items
const visibleMenuItems = menuItems.filter(item => {
  if (!item.permission) return true;
  return hasPermission(item.permission);
});
```

### 6. Action Buttons

```jsx
const UserActions = ({ user }) => {
  const { hasPermission } = usePermissions();
  
  return (
    <div className="actions">
      {hasPermission('auth.users.update') && (
        <Button onClick={() => handleEdit(user)}>Edit</Button>
      )}
      
      {hasPermission('auth.users.delete') && (
        <Button danger onClick={() => handleDelete(user)}>Delete</Button>
      )}
      
      {hasPermission('auth.users.activate') && !user.active && (
        <Button onClick={() => handleActivate(user)}>Activate</Button>
      )}
    </div>
  );
};
```

---

## Permission Loading Flow

### Initial Load

```javascript
// On app start or after login
const loadPermissions = async () => {
  try {
    const user = await authApi.getCurrentUser();
    
    // Permissions come from user object
    const permissions = user.permissions || [];
    const roles = user.roles || [];
    
    // Store in permission store
    usePermissionStore.getState().setPermissions(permissions);
    usePermissionStore.getState().setRoles(roles);
  } catch (error) {
    console.error('Failed to load permissions:', error);
  }
};
```

### After Login

```javascript
// In login handler
const handleLogin = async (credentials) => {
  const response = await authApi.login(credentials);
  
  // Store user
  useAuthStore.getState().setUser(response.data.user);
  
  // Store permissions
  usePermissionStore.getState().setPermissions(
    response.data.user.permissions || []
  );
  usePermissionStore.getState().setRoles(
    response.data.user.roles || []
  );
};
```

---

## Permission Constants

### Permission Definitions

```javascript
// lib/constants/permissions.js
export const PERMISSIONS = {
  // Auth Module
  AUTH: {
    USERS: {
      VIEW: 'auth.users.view',
      CREATE: 'auth.users.create',
      UPDATE: 'auth.users.update',
      DELETE: 'auth.users.delete',
      ACTIVATE: 'auth.users.activate',
      DEACTIVATE: 'auth.users.deactivate',
      VIEW_OWN: 'auth.users.view.own',
      UPDATE_OWN: 'auth.users.update.own',
    },
    ROLES: {
      VIEW: 'auth.roles.view',
      CREATE: 'auth.roles.create',
      UPDATE: 'auth.roles.update',
      DELETE: 'auth.roles.delete',
      ASSIGN: 'auth.roles.assign',
    },
    PERMISSIONS: {
      VIEW: 'auth.permissions.view',
      ASSIGN: 'auth.permissions.assign',
    },
  },
  
  // Ownership Module
  OWNERSHIPS: {
    VIEW: 'ownerships.view',
    CREATE: 'ownerships.create',
    UPDATE: 'ownerships.update',
    DELETE: 'ownerships.delete',
    ACTIVATE: 'ownerships.activate',
    DEACTIVATE: 'ownerships.deactivate',
    BOARD: {
      VIEW: 'ownerships.board.view',
      MANAGE: 'ownerships.board.manage',
    },
    USERS: {
      ASSIGN: 'ownerships.users.assign',
    },
  },
  
  // Add other modules...
};

// Helper to get all permissions
export const getAllPermissions = () => {
  // Flatten nested structure
  return Object.values(PERMISSIONS).flatMap(/* ... */);
};
```

---

## Advanced Patterns

### 1. Permission-Based Menu

```javascript
// hooks/useMenuItems.js
export const useMenuItems = () => {
  const { hasPermission } = usePermissions();
  
  const allMenuItems = [
    { label: 'Dashboard', path: '/dashboard' },
    { 
      label: 'Users', 
      path: '/users',
      permission: PERMISSIONS.AUTH.USERS.VIEW 
    },
    { 
      label: 'Roles', 
      path: '/roles',
      permission: PERMISSIONS.AUTH.ROLES.VIEW 
    },
  ];
  
  return allMenuItems.filter(item => {
    if (!item.permission) return true;
    return hasPermission(item.permission);
  });
};
```

### 2. Permission-Based Columns

```javascript
// In data table component
const getColumns = () => {
  const { hasPermission } = usePermissions();
  
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
  ];
  
  if (hasPermission('auth.users.update')) {
    columns.push({ key: 'actions', label: 'Actions' });
  }
  
  return columns;
};
```

### 3. Permission Context

```javascript
// contexts/PermissionContext.jsx
export const PermissionProvider = ({ 
  children 
}) => {
  const permissions = usePermissionStore(state => state.permissions);
  const hasPermission = usePermissionStore(state => state.hasPermission);
  
  return (
    <PermissionContext.Provider value={{ permissions, hasPermission }}>
      {children}
    </PermissionContext.Provider>
  );
};
```

---

## Best Practices

1. **Always Validate on Server** - Client-side is UX only
2. **Cache Permissions** - Don't fetch on every check
3. **Memoize Checks** - Use useMemo for expensive checks
4. **Graceful Degradation** - Show loading, not errors
5. **Clear Error Messages** - Tell users why access is denied
6. **Consistent Patterns** - Use same pattern throughout app

---

## Testing Permissions

### Unit Tests

```javascript
describe('PermissionGuard', () => {
  it('renders children when user has permission', () => {
    usePermissionStore.getState().setPermissions(['auth.users.view']);
    
    render(
      <PermissionGuard permission="auth.users.view">
        <div>Content</div>
      </PermissionGuard>
    );
    
    expect(screen.getByText('Content')).toBeInTheDocument();
  });
  
  it('renders fallback when user lacks permission', () => {
    usePermissionStore.getState().setPermissions([]);
    
    render(
      <PermissionGuard 
        permission="auth.users.view"
        fallback={<div>No Access</div>}
      >
        <div>Content</div>
      </PermissionGuard>
    );
    
    expect(screen.getByText('No Access')).toBeInTheDocument();
  });
});
```

---

## Migration from Roles to Permissions

If migrating from role-based to permission-based:

1. Map existing roles to permissions
2. Update all role checks to permission checks
3. Keep role checks for display purposes only
4. Gradually migrate components

---

## Notes

- Permissions are loaded from user object after login
- Permissions are cached in store
- Permission checks are synchronous (fast)
- Server always validates permissions
- Client-side is for UX only

