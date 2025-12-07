# Application Architecture & Structure

## Overview
This document defines the clean architecture structure for the Ownership Management System with API versioning and module-based organization.

---

## Directory Structure

### Root Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           └── {Module}/
│   ├── Requests/
│   │   └── V1/
│   │       └── {Module}/
│   ├── Resources/
│   │   └── V1/
│   │       └── {Module}/
│   └── Middleware/
├── Models/
│   └── V1/
│       └── {Module}/
├── Repositories/
│   └── V1/
│       └── {Module}/
│           ├── Interfaces/
│           └── {Module}Repository.php
├── Services/
│   └── V1/
│       └── {Module}/
│           └── {Module}Service.php
├── Policies/
│   └── V1/
│       └── {Module}/
│           └── {Module}Policy.php
├── Traits/
│   └── V1/
│       └── {Module}/
└── Providers/
routes/
├── api/
│   └── v1/
│       └── {module}.php
└── api.php
```

---

## Module Structure Pattern

Each module follows this structure:

### Example: Auth Module
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           └── Auth/
│   │               └── AuthController.php
│   ├── Requests/
│   │   └── V1/
│   │       └── Auth/
│   │           ├── RegisterRequest.php
│   │           ├── LoginRequest.php
│   │           └── ...
│   └── Resources/
│       └── V1/
│           └── Auth/
│               └── UserResource.php
├── Models/
│   └── V1/
│       └── Auth/
│           └── User.php
├── Repositories/
│   └── V1/
│       └── Auth/
│           ├── Interfaces/
│           │   └── UserRepositoryInterface.php
│           └── UserRepository.php
├── Services/
│   └── V1/
│       └── Auth/
│           └── AuthService.php
└── Policies/
    └── V1/
        └── Auth/
            └── UserPolicy.php

routes/
└── api/
    └── v1/
        └── auth.php
```

---

## Namespace Convention

### Pattern
```
App\{Layer}\V1\{Module}\{Class}
```

### Examples

**Controllers:**
```php
namespace App\Http\Controllers\Api\V1\Auth;
```

**Requests:**
```php
namespace App\Http\Requests\V1\Auth;
```

**Resources:**
```php
namespace App\Http\Resources\V1\Auth;
```

**Models:**
```php
namespace App\Models\V1\Auth;
```

**Repositories:**
```php
namespace App\Repositories\V1\Auth\Interfaces;
namespace App\Repositories\V1\Auth;
```

**Services:**
```php
namespace App\Services\V1\Auth;
```

**Policies:**
```php
namespace App\Policies\V1\Auth;
```

**Traits:**
```php
namespace App\Traits\V1\Auth;
```

---

## Route Structure

### Route Files
```
routes/
├── api.php                    # Main API routes file
└── api/
    └── v1/
        ├── auth.php          # Auth module routes
        ├── ownerships.php    # Ownership module routes
        ├── properties.php    # Property module routes
        └── ...
```

### Route Registration
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/ownerships.php';
    // ... other modules
});
```

### Route Naming
```php
// routes/api/v1/auth.php
Route::prefix('auth')->name('v1.auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    // ...
});
```

---

## Module Components

### 1. Controller
**Location:** `app/Http/Controllers/Api/V1/{Module}/{Module}Controller.php`

**Responsibilities:**
- Handle HTTP requests
- Validate requests
- Call services
- Return responses

**Example:**
```php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Services\V1\Auth\AuthService;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());
        return response()->json([
            'success' => true,
            'data' => new UserResource($result['user']),
        ]);
    }
}
```

---

### 2. Request Validation
**Location:** `app/Http/Requests/V1/{Module}/`

**Responsibilities:**
- Validate incoming requests
- Define validation rules
- Custom error messages

**Example:**
```php
namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

---

### 3. Resource
**Location:** `app/Http/Resources/V1/{Module}/`

**Responsibilities:**
- Transform model data for API responses
- Control what data is exposed
- Format responses consistently

**Example:**
```php
namespace App\Http\Resources\V1\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'email' => $this->email,
            // ...
        ];
    }
}
```

---

### 4. Model
**Location:** `app/Models/V1/{Module}/`

**Responsibilities:**
- Define database relationships
- Define accessors/mutators
- Define scopes
- Business logic related to the model

**Example:**
```php
namespace App\Models\V1\Auth;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'email',
        'password',
        // ...
    ];
}
```

---

### 5. Repository Interface
**Location:** `app/Repositories/V1/{Module}/Interfaces/{Module}RepositoryInterface.php`

**Responsibilities:**
- Define contract for data access
- Abstract database operations
- Enable easy testing and swapping implementations

**Example:**
```php
namespace App\Repositories\V1\Auth\Interfaces;

use App\Models\V1\Auth\User;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
}
```

---

### 6. Repository
**Location:** `app/Repositories/V1/{Module}/{Module}Repository.php`

**Responsibilities:**
- Implement repository interface
- Handle database queries
- Data access logic

**Example:**
```php
namespace App\Repositories\V1\Auth;

use App\Models\V1\Auth\User;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }
}
```

---

### 7. Service
**Location:** `app/Services/V1/{Module}/{Module}Service.php`

**Responsibilities:**
- Business logic
- Orchestrate repository calls
- Handle complex operations
- Transaction management

**Example:**
```php
namespace App\Services\V1\Auth;

use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function login(array $credentials): array
    {
        // Business logic here
        $user = $this->userRepository->findByEmail($credentials['email']);
        // ...
        return ['user' => $user, 'tokens' => $tokens];
    }
}
```

---

### 8. Policy
**Location:** `app/Policies/V1/{Module}/{Module}Policy.php`

**Responsibilities:**
- Authorization logic
- Permission checks
- Access control

**Example:**
```php
namespace App\Policies\V1\Auth;

use App\Models\V1\Auth\User;

class UserPolicy
{
    public function view(User $user, User $model): bool
    {
        return $user->can('auth.users.view');
    }
}
```

---

## Service Provider Registration

### AppServiceProvider
```php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use App\Repositories\V1\Auth\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // V1 Auth Module
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // Add other module bindings here
    }
}
```

---

## Module Creation Workflow

### Step 1: Create Folder Structure
```bash
# Create directories
mkdir -p app/Http/Controllers/Api/V1/{Module}
mkdir -p app/Http/Requests/V1/{Module}
mkdir -p app/Http/Resources/V1/{Module}
mkdir -p app/Models/V1/{Module}
mkdir -p app/Repositories/V1/{Module}/Interfaces
mkdir -p app/Services/V1/{Module}
mkdir -p app/Policies/V1/{Module}
mkdir -p routes/api/v1
```

### Step 2: Create Model
```bash
php artisan make:model Models/V1/{Module}/{Model}
```

### Step 3: Create Migration
```bash
php artisan make:migration create_{table}_table
```

### Step 4: Create Repository Interface
- Create interface in `app/Repositories/V1/{Module}/Interfaces/`

### Step 5: Create Repository
- Implement interface in `app/Repositories/V1/{Module}/`

### Step 6: Create Service
```bash
php artisan make:service Services/V1/{Module}/{Module}Service
```

### Step 7: Create Policy
```bash
php artisan make:policy Policies/V1/{Module}/{Module}Policy --model=Models/V1/{Module}/{Model}
```

### Step 8: Create Request Classes
```bash
php artisan make:request Http/Requests/V1/{Module}/{Action}Request
```

### Step 9: Create Resource
```bash
php artisan make:resource Http/Resources/V1/{Module}/{Model}Resource
```

### Step 10: Create Controller
```bash
php artisan make:controller Http/Controllers/Api/V1/{Module}/{Module}Controller
```

### Step 11: Create Routes
- Create route file in `routes/api/v1/{module}.php`
- Register in `routes/api.php`

### Step 12: Register in Service Provider
- Bind repository interfaces in `AppServiceProvider`

---

## Module List

### V1 Modules

1. **Auth** - Authentication & Authorization
   - User management
   - Login/Register
   - Token management
   - Permissions

2. **Ownerships** - Ownership Management
   - CRUD operations
   - Board members
   - User-ownership mapping

3. **Properties** - Property Management
   - Portfolios
   - Buildings
   - Units

4. **Tenants** - Tenant Management
   - Tenant profiles
   - Verification

5. **Contracts** - Contract Management
   - Contract CRUD
   - Terms management

6. **Billing** - Billing & Payments
   - Invoices
   - Payments

7. **Maintenance** - Maintenance Management
   - Requests
   - Technicians
   - Assignments

8. **Facilities** - Facility Management
   - Facilities
   - Bookings

9. **System** - System Management
   - Settings
   - Notifications
   - Audit logs

---

## Best Practices

### 1. Single Responsibility
- Each class should have one responsibility
- Controllers handle HTTP, Services handle business logic, Repositories handle data

### 2. Dependency Injection
- Always inject dependencies through constructor
- Use interfaces, not concrete classes

### 3. Versioning
- All code in V1 namespace
- Future versions (V2, V3) can coexist
- Routes prefixed with version

### 4. Module Independence
- Modules should be loosely coupled
- Shared code in common traits/services
- Module-specific code in module folders

### 5. Consistent Naming
- Use singular for models (User, not Users)
- Use plural for routes (auth, ownerships)
- Use descriptive names

### 6. Error Handling
- Use exceptions for errors
- Return consistent error responses
- Log errors appropriately

### 7. Testing
- Write tests for each layer
- Test services, repositories, controllers
- Use factories for test data

---

## Migration Strategy

### Moving Existing Code to V1 Structure

1. **Create V1 folders**
2. **Move files to V1 structure**
3. **Update namespaces**
4. **Update imports**
5. **Update route files**
6. **Update service provider**
7. **Test thoroughly**

---

## Future Versions

### V2 Structure
When creating V2:
- Copy V1 structure
- Update namespaces to V2
- Update routes to v2 prefix
- Can coexist with V1
- Gradual migration possible

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── V1/
│           └── V2/  # New version
```

---

## Notes

- All modules follow the same structure
- Consistency is key for maintainability
- Versioning allows breaking changes
- Module-based structure enables team collaboration
- Clean architecture improves testability

