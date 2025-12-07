# System Workflow & Module Architecture

## Overview
This document outlines the modular architecture and workflow for the Ownership Management System. The system is built on a permission-based access control model where **all access is determined by permissions**, and roles are used only for organizational convenience.

---

## Access Control Philosophy

### Core Principle
- **Permissions are the source of truth** - All access control is based on permissions
- **Roles are organizational tools** - Roles are simply collections of permissions for easier management
- **Role assignment is for convenience** - Users can have permissions directly or through roles

### Initial Roles
1. **Super Admin**
   - Full system access
   - Can manage all roles and permissions
   - Can create new roles
   - System-wide access across all ownerships

2. **Owner**
   - Access to their assigned ownership(s)
   - Can manage their ownership's resources
   - Limited to their ownership scope

### Role Management
- Only **Super Admin** can create new roles
- Only **Super Admin** can assign permissions to roles
- Roles can be customized per ownership (optional future enhancement)

---

## Module Architecture

The system is divided into the following modules:

### 1. Auth & Permission Module (Core)
**Status:** 🟢 Priority 1 - Foundation

**Tables:**
- `users` - System users
- `roles` (Spatie) - Role definitions
- `permissions` (Spatie) - Permission definitions
- `model_has_roles` (Spatie) - User-Role assignments
- `model_has_permissions` (Spatie) - User-Permission assignments
- `role_has_permissions` (Spatie) - Role-Permission assignments

**Key Features:**
- User authentication (email/phone)
- User registration and verification
- Role-based permission management
- Permission checking middleware
- User profile management
- Multi-ownership user support

**Permissions Structure:**
```
auth.users.view
auth.users.create
auth.users.update
auth.users.delete
auth.users.activate
auth.users.deactivate

auth.roles.view
auth.roles.create
auth.roles.update
auth.roles.delete
auth.roles.assign

auth.permissions.view
auth.permissions.assign
```

**Workflow:**
1. User registration → Email/Phone verification
2. User login → Authentication check
3. Permission check → Middleware validates permissions
4. Role assignment → Super Admin assigns roles/permissions
5. Access granted → Based on permissions, not roles

---

### 2. Ownership Module
**Status:** 🟡 Priority 2

**Tables:**
- `ownerships` - Ownership entities (companies, organizations)
- `ownership_board_members` - Board member assignments
- `user_ownership_mapping` - User-Ownership relationships

**Key Features:**
- Ownership creation and management
- Board member management
- User-ownership assignment
- Default ownership selection
- Ownership settings

**Permissions:**
```
ownerships.view
ownerships.create
ownerships.update
ownerships.delete
ownerships.activate
ownerships.deactivate
ownerships.board.view
ownerships.board.manage
ownerships.users.assign
```

---

### 3. Property Management Module
**Status:** 🟡 Priority 3

**Tables:**
- `portfolios` - Property portfolios
- `portfolio_locations` - Portfolio location data
- `buildings` - Building structures
- `building_floors` - Floor information
- `units` - Rental/lease units
- `unit_specifications` - Unit specifications

**Key Features:**
- Portfolio hierarchy management
- Building and floor management
- Unit management and specifications
- Location tracking
- Property categorization

**Permissions:**
```
properties.portfolios.view
properties.portfolios.create
properties.portfolios.update
properties.portfolios.delete
properties.buildings.view
properties.buildings.create
properties.buildings.update
properties.buildings.delete
properties.units.view
properties.units.create
properties.units.update
properties.units.delete
```

---

### 4. Tenant Management Module
**Status:** 🟡 Priority 4

**Tables:**
- `tenants` - Tenant information

**Key Features:**
- Tenant registration
- Tenant profile management
- ID verification
- Emergency contact management
- Credit rating tracking

**Permissions:**
```
tenants.view
tenants.create
tenants.update
tenants.delete
tenants.verify
tenants.rating.update
```

---

### 5. Contract Management Module
**Status:** 🟡 Priority 5

**Tables:**
- `contracts` - Rental contracts
- `contract_terms` - Contract terms

**Key Features:**
- Contract creation and management
- Contract versioning
- Digital signatures
- Contract approval workflow
- Terms management

**Permissions:**
```
contracts.view
contracts.create
contracts.update
contracts.delete
contracts.approve
contracts.sign
contracts.terminate
```

---

### 6. Billing & Payment Module
**Status:** 🟡 Priority 6

**Tables:**
- `invoices` - Rental invoices
- `invoice_items` - Invoice line items
- `payments` - Payment transactions

**Key Features:**
- Invoice generation
- Payment processing
- Payment gateway integration
- Payment tracking
- Financial reporting

**Permissions:**
```
billing.invoices.view
billing.invoices.create
billing.invoices.update
billing.invoices.delete
billing.invoices.generate
billing.payments.view
billing.payments.create
billing.payments.confirm
billing.reports.view
```

---

### 7. Maintenance Module
**Status:** 🟡 Priority 7

**Tables:**
- `maintenance_categories` - Maintenance categories
- `maintenance_requests` - Service requests
- `technicians` - Technician information
- `maintenance_assignments` - Technician assignments

**Key Features:**
- Maintenance request management
- Technician assignment
- Cost tracking
- Status workflow
- Category management

**Permissions:**
```
maintenance.categories.view
maintenance.categories.manage
maintenance.requests.view
maintenance.requests.create
maintenance.requests.update
maintenance.requests.assign
maintenance.technicians.view
maintenance.technicians.manage
```

---

### 8. Facility Management Module
**Status:** 🟡 Priority 8

**Tables:**
- `facilities` - Facility definitions
- `facility_operating_hours` - Operating hours
- `facility_bookings` - Booking reservations

**Key Features:**
- Facility management
- Booking system
- Operating hours configuration
- Booking approval workflow

**Permissions:**
```
facilities.view
facilities.create
facilities.update
facilities.delete
facilities.bookings.view
facilities.bookings.create
facilities.bookings.approve
facilities.bookings.cancel
```

---

### 9. System Module
**Status:** 🟡 Priority 9

**Tables:**
- `system_settings` - System configuration
- `system_notifications` - User notifications
- `audit_logs` - Audit trail
- `audit_log_details` - Audit details
- `documents` - Document storage
- `media_files` - Media file storage

**Key Features:**
- System configuration
- Notification system
- Audit logging
- Document management
- Media management

**Permissions:**
```
system.settings.view
system.settings.update
system.notifications.view
system.notifications.send
system.audit.view
system.documents.view
system.documents.upload
system.documents.delete
```

---

## Implementation Workflow

### Phase 1: Auth & Permission Module (Current Focus)

#### Step 1: Database Setup
- [x] Install Laravel
- [x] Install Spatie Permission package
- [x] Run migrations
- [ ] Create users table migration (if custom fields needed)
- [ ] Create seeders for initial roles and permissions

#### Step 2: Models & Relationships
- [x] Add HasRoles trait to User model
- [ ] Create User model relationships
- [ ] Create Role model extensions (if needed)
- [ ] Create Permission model extensions (if needed)

#### Step 3: Authentication System
- [ ] Create authentication controllers
- [ ] Implement login (email/phone)
- [ ] Implement registration
- [ ] Implement email verification
- [ ] Implement phone verification
- [ ] Implement password reset
- [ ] Create authentication middleware

#### Step 4: Permission System
- [ ] Define permission structure
- [ ] Create permission seeder
- [ ] Create role seeder (Super Admin, Owner)
- [ ] Create permission middleware
- [ ] Create permission checking helpers
- [ ] Create role management (Super Admin only)

#### Step 5: User Management
- [ ] Create user CRUD controllers
- [ ] Implement user listing (with filters)
- [ ] Implement user creation
- [ ] Implement user update
- [ ] Implement user activation/deactivation
- [ ] Implement role/permission assignment

#### Step 6: API/Controllers
- [ ] Create AuthController
- [ ] Create UserController
- [ ] Create RoleController (Super Admin only)
- [ ] Create PermissionController (Super Admin only)
- [ ] Implement request validation
- [ ] Implement API responses

#### Step 7: Routes
- [ ] Define authentication routes
- [ ] Define user management routes
- [ ] Define role management routes
- [ ] Define permission management routes
- [ ] Apply middleware groups

#### Step 8: Frontend/Views (if applicable)
- [ ] Login page
- [ ] Registration page
- [ ] User management interface
- [ ] Role management interface
- [ ] Permission assignment interface

#### Step 9: Testing
- [ ] Unit tests for models
- [ ] Feature tests for authentication
- [ ] Feature tests for permissions
- [ ] Feature tests for role management
- [ ] Integration tests

---

## Permission Naming Convention

### Format
`{module}.{resource}.{action}`

### Examples
- `auth.users.create` - Create users
- `ownerships.view` - View ownerships
- `properties.units.update` - Update units
- `billing.invoices.generate` - Generate invoices

### Standard Actions
- `view` - View/list resources
- `create` - Create new resources
- `update` - Update existing resources
- `delete` - Delete resources
- `activate` - Activate resources
- `deactivate` - Deactivate resources
- `assign` - Assign relationships
- `approve` - Approve actions
- `manage` - Full management (all actions)

---

## Role-Permission Assignment Strategy

### Super Admin Role
- All permissions across all modules
- Can create new roles
- Can assign permissions to roles
- System-wide access

### Owner Role
- Ownership management permissions
- Property management permissions
- Tenant management permissions
- Contract management permissions
- Billing permissions
- Maintenance permissions
- Facility permissions
- Limited to their ownership scope

### Future Roles (to be defined)
- Property Manager
- Accountant
- Maintenance Staff
- Tenant (read-only for their data)

---

## Next Steps

1. **Complete Auth & Permission Module** (Current Phase)
   - Set up all authentication flows
   - Implement permission system
   - Create initial roles and permissions
   - Build user management interface

2. **Move to Ownership Module**
   - After auth is complete
   - Implement ownership CRUD
   - Implement user-ownership mapping
   - Implement board member management

3. **Continue with Remaining Modules**
   - Follow priority order
   - Each module depends on previous modules
   - Maintain permission-based access throughout

---

## Notes

- All modules must respect permission-based access control
- Roles are organizational tools, not access gates
- Super Admin has full system access
- All other access is permission-based
- Ownership scope applies to most resources
- Audit logging should track all permission-related actions

