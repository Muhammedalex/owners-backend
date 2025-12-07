# Database Seeders Documentation

## Overview

This document describes the database seeders for the Ownership Management System. Seeders are organized by module following the V1 structure.

---

## Auth Module Seeders

### Structure

```
database/seeders/V1/Auth/
├── PermissionSeeder.php      - Creates all permissions
├── RoleSeeder.php            - Creates roles and assigns permissions
├── UserSeeder.php            - Creates initial users
└── AuthModuleSeeder.php      - Main seeder (runs all above)
```

---

## Seeders

### 1. PermissionSeeder

**Purpose:** Creates all system permissions for all modules.

**Permissions Created:**

#### Auth Module
- `auth.users.view` - View users list
- `auth.users.create` - Create users
- `auth.users.update` - Update users
- `auth.users.delete` - Delete users
- `auth.users.activate` - Activate users
- `auth.users.deactivate` - Deactivate users
- `auth.users.view.own` - View own profile
- `auth.users.update.own` - Update own profile
- `auth.roles.view` - View roles
- `auth.roles.create` - Create roles
- `auth.roles.update` - Update roles
- `auth.roles.delete` - Delete roles
- `auth.roles.assign` - Assign roles
- `auth.permissions.view` - View permissions
- `auth.permissions.assign` - Assign permissions

#### Other Modules (Future)
- Ownership permissions
- Property permissions
- Tenant permissions
- Contract permissions
- Billing permissions
- Maintenance permissions
- Facility permissions
- System permissions

**Usage:**
```bash
php artisan db:seed --class="Database\Seeders\V1\Auth\PermissionSeeder"
```

---

### 2. RoleSeeder

**Purpose:** Creates initial roles and assigns permissions.

**Roles Created:**

#### Super Admin
- **Name:** `Super Admin`
- **Permissions:** ALL permissions across all modules
- **Access:** System-wide, no restrictions
- **Can:** Create new roles, manage all permissions, full system access

#### Owner
- **Name:** `Owner`
- **Permissions:** Limited to ownership scope
- **Access:** Their ownership(s) only
- **Can:** Manage their ownership, properties, tenants, contracts, billing, maintenance, facilities

**Usage:**
```bash
php artisan db:seed --class="Database\Seeders\V1\Auth\RoleSeeder"
```

**Note:** Requires PermissionSeeder to be run first.

---

### 3. UserSeeder

**Purpose:** Creates initial users for testing and system administration.

**Users Created:**

#### Super Admin User
- **Email:** `admin@owners.com`
- **Password:** `Admin@123456`
- **Name:** Super Admin
- **Type:** `admin`
- **Role:** Super Admin
- **Status:** Active, Email Verified

#### Owner User (Test)
- **Email:** `owner@owners.com`
- **Password:** `Owner@123456`
- **Name:** Test Owner
- **Type:** `owner`
- **Role:** Owner
- **Status:** Active, Email Verified

**Usage:**
```bash
php artisan db:seed --class="Database\Seeders\V1\Auth\UserSeeder"
```

**Note:** Requires RoleSeeder to be run first.

**Security Note:** Change default passwords immediately in production!

---

### 4. AuthModuleSeeder

**Purpose:** Main seeder that runs all auth module seeders in correct order.

**Execution Order:**
1. PermissionSeeder - Creates all permissions
2. RoleSeeder - Creates roles and assigns permissions
3. UserSeeder - Creates initial users

**Usage:**
```bash
php artisan db:seed --class="Database\Seeders\V1\Auth\AuthModuleSeeder"
```

---

## Running Seeders

### Run All Seeders

```bash
php artisan db:seed
```

This runs `DatabaseSeeder` which calls `AuthModuleSeeder`.

### Run Specific Seeder

```bash
# Run only permissions
php artisan db:seed --class="Database\Seeders\V1\Auth\PermissionSeeder"

# Run only roles
php artisan db:seed --class="Database\Seeders\V1\Auth\RoleSeeder"

# Run only users
php artisan db:seed --class="Database\Seeders\V1\Auth\UserSeeder"

# Run entire auth module
php artisan db:seed --class="Database\Seeders\V1\Auth\AuthModuleSeeder"
```

### Fresh Migration with Seeding

```bash
php artisan migrate:fresh --seed
```

This will:
1. Drop all tables
2. Run all migrations
3. Run all seeders

---

## Default Credentials

After running seeders, you can login with:

### Super Admin
- **Email:** `admin@owners.com`
- **Password:** `Admin@123456`
- **Access:** Full system access

### Owner
- **Email:** `owner@owners.com`
- **Password:** `Owner@123456`
- **Access:** Owner-level access

**⚠️ IMPORTANT:** Change these passwords immediately in production!

---

## Seeder Best Practices

### 1. Idempotent Seeders
- All seeders use `firstOrCreate()` to prevent duplicates
- Safe to run multiple times
- Won't create duplicates if data exists

### 2. Order Matters
- Permissions must be created before roles
- Roles must be created before users
- Use `AuthModuleSeeder` to ensure correct order

### 3. Production Considerations
- Remove or secure default users in production
- Use environment variables for sensitive data
- Don't seed test data in production

### 4. Testing
- Seeders can be used for testing
- Create test users with known credentials
- Use factories for random test data

---

## Adding New Permissions

To add new permissions:

1. Edit `PermissionSeeder.php`
2. Add permission name to `$permissions` array
3. Run seeder:
   ```bash
   php artisan db:seed --class="Database\Seeders\V1\Auth\PermissionSeeder"
   ```

**Example:**
```php
$permissions = [
    // ... existing permissions
    'new.module.action',  // Add new permission
];
```

---

## Adding New Roles

To add new roles:

1. Edit `RoleSeeder.php`
2. Create role with `Role::firstOrCreate()`
3. Assign permissions using `syncPermissions()`
4. Run seeder:
   ```bash
   php artisan db:seed --class="Database\Seeders\V1\Auth\RoleSeeder"
   ```

**Example:**
```php
$newRole = Role::firstOrCreate(
    ['name' => 'New Role'],
    ['guard_name' => 'web']
);

$newRole->syncPermissions([
    'permission.one',
    'permission.two',
]);
```

---

## Troubleshooting

### Error: Role not found
**Problem:** UserSeeder fails with "Role not found"

**Solution:** Run RoleSeeder first:
```bash
php artisan db:seed --class="Database\Seeders\V1\Auth\RoleSeeder"
```

### Error: Permission not found
**Problem:** RoleSeeder fails with permission errors

**Solution:** Run PermissionSeeder first:
```bash
php artisan db:seed --class="Database\Seeders\V1\Auth\PermissionSeeder"
```

### Duplicate Users
**Problem:** Users already exist

**Solution:** Seeders use `firstOrCreate()` so this is safe. Existing users won't be modified unless you update the seeder.

### Can't Login
**Problem:** Can't login with seeded users

**Solution:**
- Verify users were created: `php artisan tinker` → `User::all()`
- Check email is correct
- Verify password matches seeder
- Check account is active

---

## Future Modules

When adding new modules, create seeders following this pattern:

```
database/seeders/V1/{Module}/
├── {Module}Seeder.php
└── {Module}ModuleSeeder.php
```

Then add to `DatabaseSeeder`:
```php
$this->call(\Database\Seeders\V1\{Module}\{Module}ModuleSeeder::class);
```

---

## Notes

- All seeders are idempotent (safe to run multiple times)
- Seeders follow V1 structure pattern
- Permissions are organized by module
- Roles are assigned appropriate permissions
- Default users are for development/testing only

