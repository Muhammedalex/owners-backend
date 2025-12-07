# V1 Structure Implementation Guide

## Quick Start

The V1 folder structure has been created. Now we need to migrate all existing files to the new structure.

## File Migration Checklist

### ✅ Completed
- [x] Created V1 folder structure
- [x] Created architecture documentation

### 🔄 To Do

#### 1. Repositories
- [ ] Move `UserRepositoryInterface.php` → `app/Repositories/V1/Auth/Interfaces/UserRepositoryInterface.php`
  - Update namespace: `App\Repositories\V1\Auth\Interfaces`
  - Update User model reference: `App\Models\V1\Auth\User`

- [ ] Move `UserRepository.php` → `app/Repositories/V1/Auth/UserRepository.php`
  - Update namespace: `App\Repositories\V1\Auth`
  - Update interface reference: `App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface`
  - Update User model reference: `App\Models\V1\Auth\User`

#### 2. Models
- [ ] Move `User.php` → `app/Models/V1/Auth/User.php`
  - Update namespace: `App\Models\V1\Auth`
  - Update trait references: `App\Traits\V1\Auth\*`

#### 3. Traits
- [ ] Move `HasUuid.php` → `app/Traits/V1/Auth/HasUuid.php`
  - Update namespace: `App\Traits\V1\Auth`

- [ ] Move `GeneratesTokens.php` → `app/Traits/V1/Auth/GeneratesTokens.php`
  - Update namespace: `App\Traits\V1\Auth`

- [ ] Move `LogsActivity.php` → `app/Traits/V1/Auth/LogsActivity.php`
  - Update namespace: `App\Traits\V1\Auth`

#### 4. Services
- [ ] Move `AuthService.php` → `app/Services/V1/Auth/AuthService.php`
  - Update namespace: `App\Services\V1\Auth`
  - Update repository reference: `App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface`
  - Update User model reference: `App\Models\V1\Auth\User`

#### 5. Policies
- [ ] Move `UserPolicy.php` → `app/Policies/V1/Auth/UserPolicy.php`
  - Update namespace: `App\Policies\V1\Auth`
  - Update User model reference: `App\Models\V1\Auth\User`

#### 6. Requests
- [ ] Move all `app/Http/Requests/Auth/*.php` → `app/Http/Requests/V1/Auth/*.php`
  - Update namespace: `App\Http\Requests\V1\Auth`
  - Files:
    - RegisterRequest.php
    - LoginRequest.php
    - RefreshTokenRequest.php
    - VerifyEmailRequest.php
    - ForgotPasswordRequest.php
    - ResetPasswordRequest.php

#### 7. Resources
- [ ] Move `UserResource.php` → `app/Http/Resources/V1/Auth/UserResource.php`
  - Update namespace: `App\Http\Resources\V1\Auth`

#### 8. Controllers
- [ ] Move `AuthController.php` → `app/Http/Controllers/Api/V1/Auth/AuthController.php`
  - Update namespace: `App\Http\Controllers\Api\V1\Auth`
  - Update all request references: `App\Http\Requests\V1\Auth\*`
  - Update resource reference: `App\Http\Resources\V1\Auth\UserResource`
  - Update service reference: `App\Services\V1\Auth\AuthService`

#### 9. Routes
- [ ] Move `routes/api/auth.php` → `routes/api/v1/auth.php`
  - Update controller reference: `App\Http\Controllers\Api\V1\Auth\AuthController`
  - Update route prefix: `v1/auth`
  - Update route names: `v1.auth.*`

- [ ] Update `routes/api.php`
  - Add V1 route group
  - Include v1/auth.php

#### 10. Service Provider
- [ ] Update `AppServiceProvider.php`
  - Update repository binding:
    ```php
    $this->app->bind(
        \App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface::class,
        \App\Repositories\V1\Auth\UserRepository::class
    );
    ```

#### 11. Factories & Seeders
- [ ] Update UserFactory (if exists)
  - Update model reference: `App\Models\V1\Auth\User`

- [ ] Update seeders
  - Update model references: `App\Models\V1\Auth\User`

#### 12. Migrations
- [ ] Update migration references (if any)
  - Model references in migrations

#### 13. Tests
- [ ] Update test files
  - Update all namespace references
  - Update model references

## Namespace Mapping

| Old Namespace | New Namespace |
|--------------|---------------|
| `App\Models\User` | `App\Models\V1\Auth\User` |
| `App\Repositories\UserRepositoryInterface` | `App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface` |
| `App\Repositories\UserRepository` | `App\Repositories\V1\Auth\UserRepository` |
| `App\Services\AuthService` | `App\Services\V1\Auth\AuthService` |
| `App\Policies\UserPolicy` | `App\Policies\V1\Auth\UserPolicy` |
| `App\Http\Requests\Auth\*` | `App\Http\Requests\V1\Auth\*` |
| `App\Http\Resources\UserResource` | `App\Http\Resources\V1\Auth\UserResource` |
| `App\Http\Controllers\Api\AuthController` | `App\Http\Controllers\Api\V1\Auth\AuthController` |
| `App\Traits\*` | `App\Traits\V1\Auth\*` |

## Route Changes

### Old Route Structure
```
POST /api/auth/register
POST /api/auth/login
```

### New Route Structure
```
POST /api/v1/auth/register
POST /api/v1/auth/login
```

## Testing After Migration

1. Test all authentication endpoints
2. Verify token generation works
3. Verify refresh token works
4. Check all imports are correct
5. Verify no broken references

## Next Steps After Migration

1. Delete old files
2. Update documentation
3. Update API documentation
4. Create module templates for future modules

