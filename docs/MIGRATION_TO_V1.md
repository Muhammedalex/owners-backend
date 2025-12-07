# Migration to V1 Structure - Step by Step

## Overview
This document outlines the step-by-step process to migrate existing code to the V1 structure.

## Migration Steps

### Step 1: Move and Update Auth Controller
- **From:** `app/Http/Controllers/Api/AuthController.php`
- **To:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`
- **Namespace:** `App\Http\Controllers\Api\V1\Auth`

### Step 2: Move and Update Request Classes
- **From:** `app/Http/Requests/Auth/*.php`
- **To:** `app/Http/Requests/V1/Auth/*.php`
- **Namespace:** `App\Http\Requests\V1\Auth`

### Step 3: Move and Update Resources
- **From:** `app/Http/Resources/UserResource.php`
- **To:** `app/Http/Resources/V1/Auth/UserResource.php`
- **Namespace:** `App\Http\Resources\V1\Auth`

### Step 4: Move and Update Models
- **From:** `app/Models/User.php`
- **To:** `app/Models/V1/Auth/User.php`
- **Namespace:** `App\Models\V1\Auth`

### Step 5: Move and Update Repositories
- **From:** `app/Repositories/UserRepositoryInterface.php`
- **To:** `app/Repositories/V1/Auth/Interfaces/UserRepositoryInterface.php`
- **Namespace:** `App\Repositories\V1\Auth\Interfaces`

- **From:** `app/Repositories/UserRepository.php`
- **To:** `app/Repositories/V1/Auth/UserRepository.php`
- **Namespace:** `App\Repositories\V1\Auth`

### Step 6: Move and Update Services
- **From:** `app/Services/AuthService.php`
- **To:** `app/Services/V1/Auth/AuthService.php`
- **Namespace:** `App\Services\V1\Auth`

### Step 7: Move and Update Policies
- **From:** `app/Policies/UserPolicy.php`
- **To:** `app/Policies/V1/Auth/UserPolicy.php`
- **Namespace:** `App\Policies\V1\Auth`

### Step 8: Move and Update Traits
- **From:** `app/Traits/*.php`
- **To:** `app/Traits/V1/Auth/*.php`
- **Namespace:** `App\Traits\V1\Auth`

### Step 9: Update Routes
- **From:** `routes/api/auth.php`
- **To:** `routes/api/v1/auth.php`
- **Update:** Route prefix to `v1/auth`

### Step 10: Update Service Provider
- Update repository bindings with new namespaces
- Update model references

### Step 11: Update All Imports
- Update all `use` statements throughout the codebase
- Update factory references
- Update migration references

### Step 12: Clean Up
- Remove old files after migration
- Test all endpoints
- Verify all functionality works

