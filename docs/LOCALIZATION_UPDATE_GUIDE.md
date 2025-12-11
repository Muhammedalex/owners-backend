# Localization Update Guide - دليل تحديث الترجمة

This guide shows how to update all controllers and requests to support localization.

هذا الدليل يوضح كيفية تحديث جميع الـ controllers والـ requests لدعم الترجمة.

## Pattern for Controllers - نمط Controllers

### 1. Add Trait - إضافة Trait

```php
use App\Traits\HasLocalizedResponse;

class YourController extends Controller
{
    use HasLocalizedResponse;
}
```

### 2. Replace response()->json() - استبدال response()->json()

**Before - قبل:**
```php
return response()->json([
    'success' => true,
    'message' => 'Created successfully.',
    'data' => $data,
], 201);
```

**After - بعد:**
```php
return $this->successResponse($data, 'messages.success.created', 201);
```

**Before - قبل:**
```php
return response()->json([
    'success' => false,
    'message' => 'Not found.',
], 404);
```

**After - بعد:**
```php
return $this->notFoundResponse('module.not_found');
```

**Before - قبل:**
```php
return response()->json([
    'success' => false,
    'message' => 'Ownership scope is required.',
], 400);
```

**After - بعد:**
```php
return $this->errorResponse('messages.errors.ownership_required', 400);
```

**Before - قبل:**
```php
abort(403, 'Permission denied');
```

**After - بعد:**
```php
return $this->forbiddenResponse('messages.errors.permission_denied');
```

### 3. Replace abort() - استبدال abort()

**Before - قبل:**
```php
abort(403, 'Only Super Admin can access');
```

**After - بعد:**
```php
return $this->forbiddenResponse('messages.errors.only_super_admin');
```

**Before - قبل:**
```php
abort(401, 'Unauthorized');
```

**After - بعد:**
```php
return $this->unauthorizedResponse();
```

## Pattern for Requests - نمط Requests

### 1. Add attributes() method - إضافة method attributes()

```php
public function attributes(): array
{
    return [
        'field_name' => __('messages.attributes.field_name'),
        'email' => __('messages.attributes.email'),
        'password' => __('messages.attributes.password'),
        // Add all fields used in rules()
    ];
}
```

### 2. Add messages() method (optional) - إضافة method messages() (اختياري)

```php
public function messages(): array
{
    return [
        'field_name.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.field_name')]),
        'field_name.email' => __('messages.validation.email', ['attribute' => __('messages.attributes.field_name')]),
        'field_name.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.field_name')]),
        // Add custom messages for specific rules
    ];
}
```

## Translation Keys Reference - مرجع مفاتيح الترجمة

### Success Messages - رسائل النجاح
- `messages.success.created` - Created successfully
- `messages.success.updated` - Updated successfully
- `messages.success.deleted` - Deleted successfully
- `messages.success.retrieved` - Retrieved successfully
- `messages.success.uploaded` - Uploaded successfully
- `messages.success.approved` - Approved successfully
- `messages.success.sent` - Sent successfully
- `messages.success.marked_paid` - Marked as paid
- `messages.success.marked_unpaid` - Marked as unpaid

### Error Messages - رسائل الأخطاء
- `messages.errors.not_found` - Not found
- `messages.errors.unauthorized` - Unauthorized
- `messages.errors.forbidden` - Forbidden
- `messages.errors.ownership_required` - Ownership scope is required
- `messages.errors.entity_not_found` - Entity not found
- `messages.errors.no_access` - You do not have access to this entity
- `messages.errors.permission_denied` - You don't have permission to perform this action
- `messages.errors.only_super_admin` - Only Super Admin can access

### Module-Specific Messages - رسائل خاصة بالوحدات
- `tenants.created` - Tenant created successfully
- `tenants.updated` - Tenant updated successfully
- `tenants.deleted` - Tenant deleted successfully
- `tenants.not_found` - Tenant not found
- `contracts.created` - Contract created successfully
- `contracts.approved` - Contract approved successfully
- `invoices.created` - Invoice created successfully
- `invoices.marked_paid` - Invoice marked as paid
- `payments.created` - Payment created successfully
- `settings.created` - Setting created successfully
- `documents.uploaded` - Document uploaded successfully
- `media.uploaded` - File uploaded successfully

## Checklist - قائمة التحقق

For each Controller:
- [ ] Add `use HasLocalizedResponse;` trait
- [ ] Replace all `response()->json()` with helper methods
- [ ] Replace all `abort()` with `forbiddenResponse()` or `unauthorizedResponse()`
- [ ] Test with both languages (ar/en)

For each Request:
- [ ] Add `attributes()` method with all field names
- [ ] Add `messages()` method for custom validation messages (optional)
- [ ] Test validation messages in both languages

## Example - مثال

### Controller Example

```php
<?php

namespace App\Http\Controllers\Api\V1\YourModule;

use App\Http\Controllers\Controller;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YourController extends Controller
{
    use HasLocalizedResponse;

    public function index(Request $request): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $data = YourModel::where('ownership_id', $ownershipId)->get();

        return $this->successResponse(
            YourResource::collection($data),
            'messages.success.retrieved'
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $data = $request->validated();
        $data['ownership_id'] = $ownershipId;

        $model = YourModel::create($data);

        return $this->successResponse(
            new YourResource($model),
            'your_module.created',
            201
        );
    }

    public function show(Request $request, YourModel $model): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $model->ownership_id != $ownershipId) {
            return $this->notFoundResponse('your_module.not_found');
        }

        return $this->successResponse(new YourResource($model));
    }

    public function update(UpdateRequest $request, YourModel $model): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $model->ownership_id != $ownershipId) {
            return $this->notFoundResponse('your_module.not_found');
        }

        $model->update($request->validated());

        return $this->successResponse(
            new YourResource($model->fresh()),
            'your_module.updated'
        );
    }

    public function destroy(YourModel $model): JsonResponse
    {
        $this->authorize('delete', $model);

        $model->delete();

        return $this->successResponse(null, 'your_module.deleted');
    }
}
```

### Request Example

```php
<?php

namespace App\Http\Requests\V1\YourModule;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\V1\YourModule\YourModel::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:your_table,email'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('messages.attributes.name'),
            'email' => __('messages.attributes.email'),
            'status' => __('messages.attributes.status'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.name')]),
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.email')]),
            'email.email' => __('messages.validation.email', ['attribute' => __('messages.attributes.email')]),
            'email.unique' => __('messages.validation.unique', ['attribute' => __('messages.attributes.email')]),
            'status.required' => __('messages.validation.required', ['attribute' => __('messages.attributes.status')]),
            'status.in' => __('messages.validation.in', ['attribute' => __('messages.attributes.status')]),
        ];
    }
}
```

