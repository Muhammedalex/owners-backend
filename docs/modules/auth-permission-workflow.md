# Auth & Permission Module - Detailed Workflow

## Module Overview
This is the foundation module for the entire system. All access control is based on permissions, with roles serving as organizational tools for easier permission management.

---

## Database Structure

### Core Tables

#### users
- Standard Laravel users table with custom fields:
  - `uuid` - Unique identifier
  - `type` - User type (admin, owner, tenant, etc.)
  - `phone` - Phone number
  - `phone_verified_at` - Phone verification timestamp
  - `first` - First name
  - `last` - Last name
  - `company` - Company name
  - `avatar` - Avatar file path
  - `active` - Active status
  - `last_login_at` - Last login timestamp
  - `attempts` - Login attempts counter
  - `timezone` - User timezone (default: Asia/Riyadh)
  - `locale` - User locale (default: ar)

#### Spatie Permission Tables
- `roles` - Role definitions
- `permissions` - Permission definitions
- `model_has_roles` - User-Role assignments
- `model_has_permissions` - User-Permission assignments
- `role_has_permissions` - Role-Permission assignments

---

## Permission Structure

### Auth Module Permissions

#### User Management
```
auth.users.view          - View/list users
auth.users.create        - Create new users
auth.users.update        - Update existing users
auth.users.delete        - Delete users
auth.users.activate      - Activate users
auth.users.deactivate    - Deactivate users
auth.users.view.own      - View own profile only
auth.users.update.own    - Update own profile only
```

#### Role Management (Super Admin Only)
```
auth.roles.view          - View/list roles
auth.roles.create        - Create new roles
auth.roles.update        - Update existing roles
auth.roles.delete        - Delete roles
auth.roles.assign        - Assign roles to users
```

#### Permission Management (Super Admin Only)
```
auth.permissions.view    - View/list permissions
auth.permissions.assign  - Assign permissions to roles/users
```

---

## Initial Roles & Permissions

### Super Admin Role
**Permissions:** ALL permissions across all modules
- Full system access
- Can manage all roles and permissions
- Can create new roles
- System-wide access (no ownership scope restrictions)

### Owner Role
**Permissions:**
- `auth.users.view.own`
- `auth.users.update.own`
- `ownerships.*` (all ownership permissions)
- `properties.*` (all property permissions)
- `tenants.*` (all tenant permissions)
- `contracts.*` (all contract permissions)
- `billing.*` (all billing permissions)
- `maintenance.*` (all maintenance permissions)
- `facilities.*` (all facility permissions)
- Limited to their ownership scope

---

## Implementation Steps

### Step 1: Database Migrations

#### 1.1 Update Users Migration
```php
// Add custom fields to users table
- uuid (char 36, unique)
- type (varchar 50)
- phone (varchar 20)
- phone_verified_at (timestamp, nullable)
- first (varchar 100, nullable)
- last (varchar 100, nullable)
- company (varchar 255, nullable)
- avatar (varchar 255, nullable)
- active (boolean, default true)
- last_login_at (timestamp, nullable)
- attempts (int, default 0)
- timezone (varchar 50, default 'Asia/Riyadh')
- locale (varchar 10, default 'ar')
```

#### 1.2 Spatie Permission Tables
- Already installed and migrated ✓

---

### Step 2: Models

#### 2.1 User Model
- [x] Add HasRoles trait
- [ ] Add UUID generation
- [ ] Add relationships
- [ ] Add accessors/mutators
- [ ] Add scopes (active, type, etc.)
- [ ] Add helper methods

#### 2.2 Role Model (Extension)
- [ ] Create custom Role model if needed
- [ ] Add ownership scope if needed
- [ ] Add helper methods

---

### Step 3: Seeders

#### 3.1 Permission Seeder
Create all permissions for:
- Auth module
- Ownership module
- Property module
- Tenant module
- Contract module
- Billing module
- Maintenance module
- Facility module
- System module

#### 3.2 Role Seeder
- Create Super Admin role
- Create Owner role
- Assign all permissions to Super Admin
- Assign appropriate permissions to Owner

#### 3.3 User Seeder (Optional)
- Create initial Super Admin user
- Assign Super Admin role

---

### Step 4: Authentication System

#### 4.1 Authentication Controller
**Routes:**
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login (email/phone)
- `POST /api/auth/logout` - User logout
- `POST /api/auth/refresh` - Refresh token
- `POST /api/auth/verify-email` - Email verification
- `POST /api/auth/verify-phone` - Phone verification
- `POST /api/auth/resend-verification` - Resend verification
- `POST /api/auth/forgot-password` - Password reset request
- `POST /api/auth/reset-password` - Password reset

**Features:**
- Email/Phone login support
- JWT/Sanctum token authentication
- Email verification
- Phone verification (SMS)
- Password reset
- Login attempt tracking
- Account lockout after failed attempts

#### 4.2 Middleware
- `auth:sanctum` - Authentication check
- `permission:{permission}` - Permission check
- `role:{role}` - Role check (optional, for convenience)
- `active` - Check if user is active
- `verified` - Check if user is verified

---

### Step 5: User Management

#### 5.1 User Controller
**Routes:**
- `GET /api/users` - List users (with filters)
- `GET /api/users/{id}` - Get user details
- `POST /api/users` - Create user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `POST /api/users/{id}/activate` - Activate user
- `POST /api/users/{id}/deactivate` - Deactivate user
- `GET /api/users/me` - Get current user
- `PUT /api/users/me` - Update current user

**Permissions Required:**
- List: `auth.users.view`
- View: `auth.users.view` or `auth.users.view.own` (if own)
- Create: `auth.users.create`
- Update: `auth.users.update` or `auth.users.update.own` (if own)
- Delete: `auth.users.delete`
- Activate/Deactivate: `auth.users.activate` / `auth.users.deactivate`

---

### Step 6: Role Management (Super Admin Only)

#### 6.1 Role Controller
**Routes:**
- `GET /api/roles` - List roles
- `GET /api/roles/{id}` - Get role details
- `POST /api/roles` - Create role
- `PUT /api/roles/{id}` - Update role
- `DELETE /api/roles/{id}` - Delete role
- `POST /api/roles/{id}/permissions` - Assign permissions to role
- `DELETE /api/roles/{id}/permissions/{permission_id}` - Remove permission from role
- `POST /api/users/{id}/roles` - Assign role to user
- `DELETE /api/users/{id}/roles/{role_id}` - Remove role from user

**Permissions Required:**
- All routes require Super Admin role or specific permissions
- Check: `auth.roles.*` permissions

---

### Step 7: Permission Management (Super Admin Only)

#### 7.1 Permission Controller
**Routes:**
- `GET /api/permissions` - List all permissions
- `GET /api/permissions/{id}` - Get permission details
- `GET /api/permissions/module/{module}` - Get permissions by module
- `POST /api/users/{id}/permissions` - Assign permission to user
- `DELETE /api/users/{id}/permissions/{permission_id}` - Remove permission from user

**Permissions Required:**
- All routes require Super Admin role or `auth.permissions.*` permissions

---

### Step 8: Request Validation

#### 8.1 Auth Requests
- `RegisterRequest` - Registration validation
- `LoginRequest` - Login validation
- `VerifyEmailRequest` - Email verification
- `VerifyPhoneRequest` - Phone verification
- `ForgotPasswordRequest` - Password reset request
- `ResetPasswordRequest` - Password reset

#### 8.2 User Requests
- `StoreUserRequest` - Create user validation
- `UpdateUserRequest` - Update user validation

#### 8.3 Role Requests
- `StoreRoleRequest` - Create role validation
- `UpdateRoleRequest` - Update role validation
- `AssignPermissionRequest` - Assign permission validation

---

### Step 9: Services

#### 9.1 AuthService
- Handle registration logic
- Handle login logic
- Handle verification logic
- Handle password reset logic
- Handle token management

#### 9.2 UserService
- User CRUD operations
- User activation/deactivation
- User search and filtering
- User ownership assignment (future)

#### 9.3 PermissionService
- Permission checking
- Role assignment
- Permission assignment
- Permission caching

---

### Step 10: API Resources

#### 10.1 UserResource
- Transform user data for API responses
- Include roles and permissions
- Hide sensitive information

#### 10.2 RoleResource
- Transform role data
- Include permissions

#### 10.3 PermissionResource
- Transform permission data

---

### Step 11: Policies (Optional)

#### 11.1 UserPolicy
- `viewAny` - Check if user can view users list
- `view` - Check if user can view specific user
- `create` - Check if user can create users
- `update` - Check if user can update user
- `delete` - Check if user can delete user

---

### Step 12: Testing

#### 12.1 Unit Tests
- User model tests
- Permission checking tests
- Role assignment tests

#### 12.2 Feature Tests
- Authentication flow tests
- User management tests
- Role management tests
- Permission management tests
- Middleware tests

#### 12.3 Integration Tests
- Full authentication flow
- Permission-based access control
- Role assignment flow

---

## API Response Examples

### Login Response
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "user@example.com",
      "name": "John Doe",
      "roles": ["owner"],
      "permissions": ["ownerships.view", "properties.view"]
    },
    "token": "1|xxxxxxxxxxxxx"
  }
}
```

### User List Response
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "email": "user@example.com",
        "name": "John Doe",
        "active": true,
        "roles": ["owner"],
        "created_at": "2025-01-01T00:00:00.000000Z"
      }
    ],
    "meta": {
      "total": 1,
      "per_page": 15,
      "current_page": 1
    }
  }
}
```

---

## Security Considerations

1. **Password Security**
   - Use Laravel's built-in password hashing
   - Enforce strong password policies
   - Implement password reset with secure tokens

2. **Authentication Security**
   - Rate limiting on login attempts
   - Account lockout after failed attempts
   - Token expiration and refresh
   - Secure token storage

3. **Permission Security**
   - Always check permissions, never roles
   - Cache permissions for performance
   - Log all permission checks
   - Validate ownership scope

4. **API Security**
   - Use HTTPS only
   - Implement CORS properly
   - Validate all inputs
   - Sanitize outputs
   - Rate limiting on API endpoints

---

## Next Steps After Auth Module

1. Complete all authentication flows
2. Test thoroughly
3. Document API endpoints
4. Move to Ownership Module
5. Implement ownership-scoped permissions

---

## Notes

- All access is permission-based, not role-based
- Roles are organizational tools only
- Super Admin has all permissions
- Ownership scope will be added in next module
- All actions should be logged for audit purposes

