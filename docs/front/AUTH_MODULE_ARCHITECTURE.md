# Auth Module - Detailed Architecture

## Overview

Complete architecture for the authentication and permission system in the React frontend application.

---

## Module Structure

```
features/auth/
├── components/          # UI Components
├── hooks/              # Custom Hooks
├── guards/             # Route Guards
├── services/           # Business Logic
├── store/              # State Management
├── utils/              # Utilities
└── index.js            # Public API
```

---

## Components

### 1. LoginForm Component

**Location:** `features/auth/components/LoginForm/LoginForm.jsx`

**Purpose:** User login form with email/phone support

**Features:**
- Email or phone login
- Password field
- Remember me option
- Forgot password link
- Form validation
- Error handling
- Loading states

**Props:**
```javascript
{
  onSuccess?: (data) => void,
  onError?: (error) => void
}
```

**Usage:**
```jsx
<LoginForm 
  onSuccess={(data) => navigate('/dashboard')}
  onError={(error) => showError(error.message)}
/>
```

---

### 2. RegisterForm Component

**Location:** `features/auth/components/RegisterForm/RegisterForm.jsx`

**Purpose:** User registration form

**Features:**
- Email registration
- Password confirmation
- Optional phone number
- First/Last name fields
- Company field
- Form validation
- Terms acceptance

---

### 3. ForgotPasswordForm Component

**Location:** `features/auth/components/ForgotPasswordForm/ForgotPasswordForm.jsx`

**Purpose:** Request password reset

**Features:**
- Email input
- Submit button
- Success/error messages

---

### 4. ResetPasswordForm Component

**Location:** `features/auth/components/ResetPasswordForm/ResetPasswordForm.jsx`

**Purpose:** Reset password with token

**Features:**
- Token validation
- New password input
- Password confirmation
- Submit handler

---

### 5. AuthLayout Component

**Location:** `features/auth/components/AuthLayout/AuthLayout.jsx`

**Purpose:** Layout wrapper for auth pages

**Features:**
- Centered layout
- Logo/branding
- Responsive design
- Background styling

---

## Hooks

### 1. useAuth Hook

**Location:** `features/auth/hooks/useAuth.js`

**Purpose:** Main authentication hook

**Returns:**
```javascript
{
  user: null, // User object or null
  isAuthenticated: false, // boolean
  isLoading: false, // boolean
  login: async (credentials) => {},
  register: async (data) => {},
  logout: async () => {},
  logoutAll: async () => {},
  refreshToken: async () => {},
  checkAuth: async () => {},
}
```

**Usage:**
```jsx
const { user, isAuthenticated, login, logout } = useAuth();
```

---

### 2. useLogin Hook

**Location:** `features/auth/hooks/useLogin.js`

**Purpose:** Login mutation hook

**Returns:**
```javascript
{
  login: async (credentials) => {},
  isLoading: false, // boolean
  error: null, // Error or null
  isSuccess: false, // boolean
}
```

**Usage:**
```jsx
const { login, isLoading, error } = useLogin();
```

---

### 3. useRegister Hook

**Location:** `features/auth/hooks/useRegister.js`

**Purpose:** Registration mutation hook

**Returns:**
```javascript
{
  register: async (data) => {},
  isLoading: false, // boolean
  error: null, // Error or null
  isSuccess: false, // boolean
}
```

---

### 4. usePermissions Hook

**Location:** `features/auth/hooks/usePermissions.js`

**Purpose:** Permission management hook

**Returns:**
```javascript
{
  permissions: [], // string[]
  roles: [], // string[]
  hasPermission: (permission) => boolean,
  hasAnyPermission: (permissions) => boolean,
  hasAllPermissions: (permissions) => boolean,
  hasRole: (role) => boolean,
  isLoading: false, // boolean
  refreshPermissions: async () => {},
}
```

**Usage:**
```jsx
const { hasPermission, hasRole } = usePermissions();

if (hasPermission('auth.users.view')) {
  // Show users page
}

if (hasRole('Super Admin')) {
  // Show admin features
}
```

---

### 5. useHasPermission Hook

**Location:** `features/auth/hooks/useHasPermission.js`

**Purpose:** Single permission check hook

**Returns:**
```javascript
{
  hasPermission: false, // boolean
  isLoading: false, // boolean
}
```

**Usage:**
```jsx
const { hasPermission } = useHasPermission('auth.users.view');

{hasPermission && <UsersPage />}
```

---

### 6. useRefreshToken Hook

**Location:** `features/auth/hooks/useRefreshToken.js`

**Purpose:** Automatic token refresh

**Features:**
- Automatic refresh before expiry
- Background refresh
- Error handling
- Retry logic

---

## Guards

### 1. ProtectedRoute Component

**Location:** `features/auth/guards/ProtectedRoute.jsx`

**Purpose:** Route guard for authenticated users

**Usage:**
```jsx
<Route
  path="/dashboard"
  element={
    <ProtectedRoute>
      <DashboardPage />
    </ProtectedRoute>
  }
/>
```

**Features:**
- Checks authentication
- Redirects to login if not authenticated
- Shows loading state
- Handles token refresh

---

### 2. PublicRoute Component

**Location:** `features/auth/guards/PublicRoute.jsx`

**Purpose:** Route guard for public pages (redirects if authenticated)

**Usage:**
```jsx
<Route
  path="/login"
  element={
    <PublicRoute>
      <LoginPage />
    </PublicRoute>
  }
/>
```

**Features:**
- Redirects to dashboard if authenticated
- Allows access if not authenticated

---

### 3. PermissionRoute Component

**Location:** `features/auth/guards/PermissionRoute.jsx`

**Purpose:** Route guard based on permissions

**Usage:**
```jsx
<Route
  path="/users"
  element={
    <PermissionRoute permission="auth.users.view">
      <UsersPage />
    </PermissionRoute>
  }
/>
```

**Features:**
- Checks specific permission
- Redirects if no permission
- Shows access denied message

**Props:**
```javascript
{
  permission: string | string[],
  requireAll?: boolean, // If multiple permissions, require all
  fallback?: React.ReactNode,
  redirectTo?: string,
}
```

---

### 4. RoleRoute Component

**Location:** `features/auth/guards/RoleRoute.jsx`

**Purpose:** Route guard based on roles

**Usage:**
```jsx
<Route
  path="/admin"
  element={
    <RoleRoute role="Super Admin">
      <AdminPage />
    </RoleRoute>
  }
/>
```

---

## Services

### 1. Auth Service

**Location:** `features/auth/services/auth.service.js`

**Purpose:** Authentication business logic

**Methods:**
```javascript
class AuthService {
  async login(credentials) {}
  async register(data) {}
  async logout(refreshToken) {}
  async logoutAll() {}
  async refreshAccessToken() {}
  async getCurrentUser() {}
  async verifyEmail(id, hash) {}
  async resendVerificationEmail() {}
  async forgotPassword(email) {}
  async resetPassword(data) {}
}
```

---

### 2. Token Service

**Location:** `features/auth/services/token.service.js`

**Purpose:** Token management

**Methods:**
```javascript
class TokenService {
  getAccessToken() {}
  getRefreshToken() {}
  setTokens(tokens) {}
  clearTokens() {}
  isAccessTokenExpired() {}
  isRefreshTokenExpired() {}
  getTokenExpiry() {}
  shouldRefreshToken() {}
}
```

**Storage:**
- Access token: Memory (recommended) or localStorage
- Refresh token: httpOnly cookie (preferred) or localStorage

---

### 3. Permission Service

**Location:** `features/auth/services/permission.service.js`

**Purpose:** Permission checking logic

**Methods:**
```javascript
class PermissionService {
  hasPermission(permission) {}
  hasAnyPermission(permissions) {}
  hasAllPermissions(permissions) {}
  hasRole(role) {}
  hasAnyRole(roles) {}
  getPermissions() {}
  getRoles() {}
  refreshPermissions() {}
}
```

---

### 4. Storage Service

**Location:** `features/auth/services/storage.service.js`

**Purpose:** Local storage management

**Methods:**
```javascript
class StorageService {
  set(key, value) {}
  get(key) {}
  remove(key) {}
  clear() {}
  // Token-specific methods
  setAccessToken(token) {}
  getAccessToken() {}
  setRefreshToken(token) {}
  getRefreshToken() {}
}
```

---

## Store (State Management)

### 1. Auth Store (Zustand)

**Location:** `features/auth/store/auth.store.js`

**Structure:**
```javascript
// Auth state shape
{
  // State
  user: null, // User object or null
  accessToken: null, // string or null
  refreshToken: null, // string or null
  isAuthenticated: false, // boolean
  isLoading: false, // boolean
  
  // Actions
  setUser: (user) => {},
  setTokens: (tokens) => {},
  clearAuth: () => {},
  login: async (credentials) => {},
  register: async (data) => {},
  logout: async () => {},
  logoutAll: async () => {},
  refreshToken: async () => {},
  checkAuth: async () => {},
}
```

**Usage:**
```jsx
const { user, isAuthenticated, login, logout } = useAuthStore();
```

---

### 2. Permission Store

**Location:** `features/auth/store/permission.store.js`

**Structure:**
```javascript
// Permission state shape
{
  // State
  permissions: [], // string[]
  roles: [], // string[]
  isLoaded: false, // boolean
  
  // Actions
  setPermissions: (permissions) => {},
  setRoles: (roles) => {},
  clearPermissions: () => {},
  hasPermission: (permission) => boolean,
  hasAnyPermission: (permissions) => boolean,
  hasAllPermissions: (permissions) => boolean,
  hasRole: (role) => boolean,
  refreshPermissions: async () => {},
}
```

---

## Utils

### 1. Token Utils

**Location:** `features/auth/utils/token.utils.js`

**Functions:**
```javascript
export const parseJWT = (token) => {}
export const isTokenExpired = (token) => {}
export const getTokenExpiry = (token) => {}
export const shouldRefreshToken = (token, bufferMinutes) => {}
```

---

### 2. Permission Utils

**Location:** `features/auth/utils/permission.utils.js`

**Functions:**
```javascript
export const checkPermission = (userPermissions, required) => {}
export const checkAnyPermission = (userPermissions, required) => {}
export const checkAllPermissions = (userPermissions, required) => {}
export const checkRole = (userRoles, required) => {}
```

---

## API Integration

### Auth API

**Location:** `api/v1/auth/auth.api.js`

**Functions:**
```javascript
export const authApi = {
  login: async (credentials) => {},
  register: async (data) => {},
  logout: async (refreshToken) => {},
  logoutAll: async () => {},
  refreshToken: async (refreshToken) => {},
  getCurrentUser: async () => {},
  verifyEmail: async (id, hash) => {},
  resendVerification: async () => {},
  forgotPassword: async (email) => {},
  resetPassword: async (data) => {},
};
```

---

## Permission Component Examples

### Permission-Based Rendering

```jsx
// Using hook
const { hasPermission } = usePermissions();

{hasPermission('auth.users.view') && (
  <Link to="/users">Users</Link>
)}

// Using component
<PermissionGuard permission="auth.users.create">
  <Button onClick={handleCreate}>Create User</Button>
</PermissionGuard>
```

---

## Route Configuration

### Route Setup

```jsx
// routes/index.jsx
<Routes>
  {/* Public Routes */}
  <Route path="/" element={<LandingPage />} />
  
  <Route path="/auth" element={<AuthLayout />}>
    <Route path="login" element={<PublicRoute><LoginPage /></PublicRoute>} />
    <Route path="register" element={<PublicRoute><RegisterPage /></PublicRoute>} />
    <Route path="forgot-password" element={<PublicRoute><ForgotPasswordPage /></PublicRoute>} />
    <Route path="reset-password" element={<PublicRoute><ResetPasswordPage /></PublicRoute>} />
    <Route path="verify-email/:id/:hash" element={<PublicRoute><VerifyEmailPage /></PublicRoute>} />
  </Route>

  {/* Protected Routes */}
  <Route path="/dashboard" element={<ProtectedRoute><DashboardLayout /></ProtectedRoute>}>
    <Route index element={<DashboardPage />} />
    
    {/* Permission-based routes */}
    <Route 
      path="users" 
      element={<PermissionRoute permission="auth.users.view"><UsersPage /></PermissionRoute>} 
    />
    <Route 
      path="roles" 
      element={<PermissionRoute permission="auth.roles.view"><RolesPage /></PermissionRoute>} 
    />
  </Route>
</Routes>
```

---

## Implementation Flow

### 1. App Initialization

```
App Start
    ↓
Check for stored tokens
    ↓
If tokens exist → Validate & fetch user
    ↓
Load permissions
    ↓
Set auth state
    ↓
Render app
```

### 2. Login Flow

```
User submits login form
    ↓
Call login API
    ↓
Receive tokens & user data
    ↓
Store tokens securely
    ↓
Store user & permissions in state
    ↓
Redirect to dashboard
```

### 3. Token Refresh Flow

```
API request with access token
    ↓
Token expired (401)
    ↓
Intercept 401 response
    ↓
Use refresh token to get new tokens
    ↓
Retry original request
    ↓
If refresh fails → Logout
```

### 4. Permission Check Flow

```
Component/Route needs permission
    ↓
Check permission in store
    ↓
If has permission → Render/Allow
    ↓
If no permission → Hide/Redirect
```

---

## Security Considerations

1. **Token Storage**
   - Access token: Memory (preferred) or localStorage
   - Refresh token: httpOnly cookie (preferred) or localStorage
   - Never expose tokens in URLs

2. **XSS Prevention**
   - Sanitize user input
   - Use Content Security Policy
   - Avoid innerHTML

3. **CSRF Protection**
   - Use CSRF tokens
   - SameSite cookies

4. **Token Rotation**
   - Refresh tokens rotate on use
   - Old tokens invalidated

5. **Permission Validation**
   - Always validate on client AND server
   - Never trust client-side only

---

## Best Practices

1. **State Management**
   - Centralize auth state
   - Use React Query for server state
   - Cache permissions

2. **Error Handling**
   - Handle token expiration
   - Handle network errors
   - User-friendly error messages

3. **Performance**
   - Lazy load auth components
   - Memoize permission checks
   - Optimize re-renders

4. **UX**
   - Loading states
   - Error messages
   - Success feedback
   - Smooth transitions

---

## Testing Strategy

1. **Unit Tests**
   - Hooks
   - Services
   - Utils
   - Components

2. **Integration Tests**
   - Auth flow
   - Permission checks
   - Route guards

3. **E2E Tests**
   - Complete login flow
   - Permission-based access
   - Token refresh
