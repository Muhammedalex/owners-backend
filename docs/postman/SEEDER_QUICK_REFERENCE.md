# Seeder Quick Reference

## Quick Commands

### Run All Seeders
```bash
php artisan db:seed
```

### Run Auth Module Only
```bash
php artisan db:seed --class="Database\Seeders\V1\Auth\AuthModuleSeeder"
```

### Fresh Start (Drop & Recreate)
```bash
php artisan migrate:fresh --seed
```

---

## Default Login Credentials

After seeding:

**Super Admin:**
- Email: `admin@owners.com`
- Password: `Admin@123456`

**Owner:**
- Email: `owner@owners.com`
- Password: `Owner@123456`

---

## What Gets Created

1. **Permissions** - All system permissions (80+ permissions)
2. **Roles** - Super Admin & Owner roles
3. **Users** - Super Admin & Owner test users

---

## Seeder Order

1. PermissionSeeder → Creates permissions
2. RoleSeeder → Creates roles, assigns permissions
3. UserSeeder → Creates users, assigns roles

