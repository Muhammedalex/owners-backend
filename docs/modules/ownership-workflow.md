# Ownership Module - Workflow & Architecture

## Overview

نظام إدارة الملكيات مبني على **Ownership Scoping** - كل حاجة في النظام مربوطه بملكية محددة (`ownership_id`).

---

## User Types & Roles

### 1. Super Admin (مشرف النظام العام)
- **عدد المستخدمين:** متعدد (يمكن أن يكونوا أكثر من واحد)
- **الصلاحيات:** صلاحيات كاملة على كل النظام
- **Scope:** Global (بيشوف كل الملكيات)
- **المهام:**
  - إنشاء ملكيات جديدة
  - ربط الملكيات بيوزر مالك (default owner)
  - إدارة المستخدمين
  - إدارة الأدوار والصلاحيات
  - الوصول لكل البيانات بدون قيود

### 2. Owner (المالك)
- **عدد المستخدمين:** واحد أو أكثر لكل ملكية
- **الصلاحيات:** صلاحيات كاملة على ملكيته فقط
- **Scope:** Limited to assigned ownerships
- **المهام:**
  - إدارة ملكيته بالكامل
  - إنشاء مباني، طوابق، وحدات
  - إدارة المستأجرين (مع أو بدون user account)
  - إدارة موظفي الصيانة
  - استعراض التقارير المالية
  - إنشاء مستخدمين جدد (مستأجرين، موظفين، إلخ)
  - كل حاجة حسب الصلاحيات المنوطة به

---

## Ownership Scoping System

### المبدأ الأساسي
**كل action في التطبيق يخضع لـ permission + ownership scope**

### كيف يعمل:

1. **Ownership UUID في الكوكيز (Cookies)**
   - الـ Frontend يخزن `ownership_uuid` في cookie
   - الـ Backend يقرأ من الكوكيز تلقائياً
   - **لا نستخدم ID في أي حاجة - كل حاجة بالـ UUID**

2. **Global Middleware يتحقق من:**
   - يقرأ `ownership_uuid` من الكوكيز
   - يبحث عن الـ Ownership بالـ UUID
   - يتحقق: هل المستخدم له صلاحية على هذه الملكية؟
   - هل المستخدم Super Admin؟ (بيتخطى الـ scope check)
   - هل المستخدم Owner ومربوط بهذه الملكية؟
   - يحفظ الـ Ownership في Request للاستخدام لاحقاً

3. **Query Scoping:**
   - كل query بيتم filter بـ `ownership_id` (الداخلي من الـ UUID)
   - Owner بيشوف بس البيانات اللي تخص ملكيته
   - Super Admin بيشوف كل البيانات

### Cookie Structure:
```
Cookie Name: ownership_uuid
Cookie Value: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx (UUID format)
Cookie Attributes: httpOnly, secure (in production), SameSite=strict
```

---

## User-Ownership Relationship

### 1. User-Ownership Mapping
**الجدول:** `user_ownership_mapping`

- **العلاقة:** Many-to-Many (User يمكن يكون مربوط بأكتر من ملكية)
- **الحقل `default`:** الملكية الافتراضية للمستخدم
- **الاستخدام:** المستخدم بيقدر ينتقل بين الملكيات

### 2. Ownership Board Members
**الجدول:** `ownership_board_members`

- **العلاقة:** أعضاء مجلس الإدارة لكل ملكية
- **الحقول:** `role`, `start_date`, `end_date`, `active`
- **الاستخدام:** تتبع أدوار المستخدمين في الملكية

---

## Workflow

### 1. Super Admin Creates Ownership & Owner

```
Super Admin
    ↓
Creates New User (Owner)
    ↓
Creates New Ownership
    ↓
Links User to Ownership (user_ownership_mapping)
    - Sets as default ownership
    - Assigns "Owner" role
    ↓
Owner can now access their ownership
```

### 2. Owner Manages Their Ownership

```
Owner (logged in with ownership_id scope)
    ↓
Can Create:
    - Buildings, Floors, Units
    - Tenants (with or without user account)
    - Maintenance Technicians
    - Other Users (with appropriate roles)
    ↓
Can View:
    - Financial Reports (scoped to their ownership)
    - All data related to their ownership
    ↓
All actions are scoped to ownership_id
```

### 3. Multi-Ownership User

```
User with Multiple Ownerships
    ↓
Selects Active Ownership (sets default)
    ↓
All subsequent requests use selected ownership_id
    ↓
Can switch between ownerships
    ↓
Each ownership has separate data scope
```

---

## Global Middleware Logic

### Ownership Scope Middleware

```php
// Pseudo-code
// 1. Read ownership_uuid from cookie
$ownershipUuid = $request->cookie('ownership_uuid');

// 2. If no cookie, try to get default ownership for user
if (!$ownershipUuid && !$user->isSuperAdmin()) {
    $defaultOwnership = $user->getDefaultOwnership();
    if ($defaultOwnership) {
        $ownershipUuid = $defaultOwnership->uuid;
        // Set cookie for next requests
    } else {
        return 403 Forbidden; // User has no ownership
    }
}

// 3. Find ownership by UUID
$ownership = Ownership::where('uuid', $ownershipUuid)->first();

if (!$ownership) {
    return 404 Not Found; // Ownership not found
}

// 4. Check access (Super Admin bypass)
if (!$user->isSuperAdmin()) {
    if (!$user->hasOwnership($ownership->id)) {
        return 403 Forbidden; // User doesn't have access
    }
}

// 5. Store ownership in request for later use
$request->merge(['current_ownership' => $ownership]);
$request->merge(['current_ownership_id' => $ownership->id]);

// 6. Apply scope to all queries (in Global Scope)
// $query->where('ownership_id', $ownership->id);
```

### Permission Check Flow

```
Request arrives
    ↓
Middleware reads ownership_uuid from cookie
    ↓
Find Ownership by UUID
    ↓
Check Permission (e.g., 'buildings.create')
    ↓
Check Ownership Scope (user has access to this ownership)
    ↓
Store ownership in request context
    ↓
If both pass → Allow
If either fails → Deny (403)
```

---

## Key Principles

1. **Permission-Based Access Control**
   - كل action يحتاج permission
   - Permissions hard-coded في seeders

2. **Ownership Scoping**
   - كل data مربوط بـ `ownership_id`
   - Owner بيشوف بس ملكيته
   - Super Admin بيشوف كل حاجة

3. **Role Assignment**
   - Roles هي groups of permissions
   - Owner role = كل permissions للملكية
   - Roles يمكن إدارتها عبر API

4. **User-Ownership Mapping**
   - User يمكن يكون مربوط بأكتر من ملكية
   - `default` flag للملكية الافتراضية
   - User ينتقل بين الملكيات

---

## Database Structure

### Core Tables

1. **ownerships**
   - بيانات الملكية الأساسية
   - `created_by` → User اللي أنشأها

2. **user_ownership_mapping**
   - ربط المستخدمين بالملكيات
   - `default` flag

3. **ownership_board_members**
   - أعضاء مجلس الإدارة
   - أدوار المستخدمين في الملكية

---

## API Endpoints Structure

**ملاحظة مهمة:** كل الـ endpoints تستخدم UUID في الـ route parameters، وليس ID

### Ownership Management
- `GET /api/v1/ownerships` - List (scoped by cookie)
- `POST /api/v1/ownerships` - Create (Super Admin only)
- `GET /api/v1/ownerships/{uuid}` - View (scoped by cookie)
- `PUT /api/v1/ownerships/{uuid}` - Update (scoped by cookie)
- `DELETE /api/v1/ownerships/{uuid}` - Delete (Super Admin only)
- `POST /api/v1/ownerships/{uuid}/switch` - Switch active ownership (sets cookie)

### User-Ownership Mapping
- `POST /api/v1/ownerships/{uuid}/users/assign` - Assign user
- `DELETE /api/v1/ownerships/{uuid}/users/{userUuid}` - Remove user
- `GET /api/v1/users/{uuid}/ownerships` - Get user's ownerships

### Board Members
- `GET /api/v1/ownerships/{uuid}/board-members` - List (scoped by cookie)
- `POST /api/v1/ownerships/{uuid}/board-members` - Add (scoped by cookie)
- `DELETE /api/v1/ownerships/{uuid}/board-members/{uuid}` - Remove (scoped by cookie)

---

## Security Considerations

1. **Always Validate Ownership Scope**
   - Middleware يقرأ ownership_uuid من cookie في كل request
   - يتحقق من صحة UUID ووجود الملكية
   - Query scoping يمنع data leakage

2. **Permission + Scope Check**
   - Permission check أولاً
   - ثم Ownership scope check

3. **Super Admin Bypass**
   - Super Admin يتخطى scope checks
   - لكن لسه محتاج permissions

4. **Default Ownership**
   - User لازم يكون عنده default ownership
   - لو مش موجود cookie، يستخدم default ownership
   - الـ middleware يضبط cookie تلقائياً في حالة عدم وجوده

5. **Cookie Security**
   - Cookie يكون httpOnly (لا يمكن الوصول من JavaScript)
   - secure في production (HTTPS only)
   - SameSite=strict (CSRF protection)

---

## Example Scenarios

### Scenario 1: Owner Views Buildings
```
Request: GET /api/v1/buildings
Cookie: ownership_uuid=xxxx-xxxx-xxxx-xxxx
    ↓
Middleware:
    - Reads ownership_uuid from cookie
    - Finds Ownership by UUID
    - User has 'buildings.view' permission? ✓
    - User has access to this ownership? ✓
    ↓
Query: Building::where('ownership_id', $ownership->id)->get()
    ↓
Response: Only buildings for current ownership
```

### Scenario 2: Super Admin Views All Buildings
```
Request: GET /api/v1/buildings
Cookie: ownership_uuid=xxxx-xxxx-xxxx-xxxx (optional)
    ↓
Middleware:
    - User is Super Admin? ✓
    - User has 'buildings.view' permission? ✓
    - Bypass ownership scope check
    ↓
Query: Building::all() (no scope restriction)
    ↓
Response: All buildings
```

### Scenario 3: Owner Tries to Access Another Ownership
```
Request: GET /api/v1/buildings
Cookie: ownership_uuid=yyyy-yyyy-yyyy-yyyy (different ownership)
    ↓
Middleware:
    - Reads ownership_uuid from cookie
    - Finds Ownership by UUID
    - User has 'buildings.view' permission? ✓
    - User has access to this ownership? ✗
    ↓
Response: 403 Forbidden
```

### Scenario 4: User Switches Ownership
```
Frontend: User selects different ownership
    ↓
Request: POST /api/v1/ownerships/{uuid}/switch
    ↓
Backend:
    - Validates user has access to this ownership
    - Sets new ownership_uuid cookie
    ↓
Response: Success
    ↓
All subsequent requests use new ownership_uuid from cookie
```

---

## Next Steps

1. Create Ownership Model (with HasUuid trait)
2. Create Ownership Repository & Service (use UUID, not ID)
3. Create Ownership Controller (routes use {uuid} parameter)
4. Create Ownership Policy (check by UUID)
5. Create Global Ownership Scope Middleware (read from cookie)
6. Create User-Ownership Mapping endpoints (use UUIDs)
7. Create Board Members endpoints (use UUIDs)
8. Implement Query Scoping in all models (use ownership_id internally)
9. Create Cookie Helper Service (set/read ownership_uuid cookie)

---

## Important Notes

### UUID vs ID Usage

1. **API Routes & Parameters:**
   - ✅ Use UUID: `/api/v1/ownerships/{uuid}`
   - ❌ Never use ID: `/api/v1/ownerships/{id}`

2. **Database Queries:**
   - Use UUID for finding: `Ownership::where('uuid', $uuid)->first()`
   - Use ID for relationships: `$ownership->id` (internal only)

3. **Cookie Storage:**
   - Store UUID in cookie: `ownership_uuid=xxxx-xxxx-xxxx-xxxx`
   - Never store ID in cookie

4. **Request Context:**
   - Middleware converts UUID → Ownership → stores ID in request
   - Controllers use `$request->current_ownership_id` for queries

5. **Response Format:**
   - Always return UUID in API responses
   - Never expose internal IDs to frontend

