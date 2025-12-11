# Localization Guide - دليل الترجمة

## Overview - نظرة عامة

The application supports Arabic (ar) and English (en) languages for all API responses and validation messages.

التطبيق يدعم اللغة العربية والإنجليزية لجميع استجابات API ورسائل التحقق.

## How It Works - كيف يعمل

### 1. Language Detection - اكتشاف اللغة

The language is detected from (in order of priority):
1. `Accept-Language` header (e.g., `Accept-Language: ar`)
2. `lang` query parameter (e.g., `?lang=ar`)
3. `locale` cookie
4. Default locale from config (`APP_LOCALE`)

يتم اكتشاف اللغة من (بترتيب الأولوية):
1. Header `Accept-Language` (مثال: `Accept-Language: ar`)
2. Query parameter `lang` (مثال: `?lang=ar`)
3. Cookie `locale`
4. اللغة الافتراضية من الإعدادات (`APP_LOCALE`)

### 2. Language Files - ملفات اللغة

Language files are located in `lang/` directory:
- `lang/ar.json` - Arabic translations
- `lang/en.json` - English translations

ملفات اللغة موجودة في مجلد `lang/`:
- `lang/ar.json` - الترجمات العربية
- `lang/en.json` - الترجمات الإنجليزية

### 3. Using Translations in Controllers - استخدام الترجمة في Controllers

#### Step 1: Use the Trait - الخطوة 1: استخدام الـ Trait

```php
use App\Traits\HasLocalizedResponse;

class YourController extends Controller
{
    use HasLocalizedResponse;
}
```

#### Step 2: Use Helper Methods - الخطوة 2: استخدام الـ Helper Methods

```php
// Success response with message
return $this->successResponse($data, 'messages.success.created', 201);

// Error response
return $this->errorResponse('messages.errors.not_found', 404);

// Not found response
return $this->notFoundResponse('messages.errors.not_found');

// Unauthorized response
return $this->unauthorizedResponse();

// Forbidden response
return $this->forbiddenResponse('messages.errors.forbidden');
```

#### Step 3: Direct Translation - الخطوة 3: الترجمة المباشرة

```php
// Using __() helper
$message = __('messages.success.created');

// With parameters
$message = __('settings.permission_denied', ['group' => 'financial']);
```

### 4. Validation Messages - رسائل التحقق

Validation messages are automatically translated. Add custom messages in language files:

رسائل التحقق تُترجم تلقائياً. أضف رسائل مخصصة في ملفات اللغة:

```php
// In Request class
public function messages(): array
{
    return [
        'email.required' => __('messages.validation.required', ['attribute' => 'email']),
        'email.email' => __('messages.validation.email', ['attribute' => 'email']),
    ];
}
```

Or use the `attributes()` method for field names:

أو استخدم method `attributes()` لأسماء الحقول:

```php
public function attributes(): array
{
    return [
        'email' => __('validation.attributes.email'),
        'password' => __('validation.attributes.password'),
    ];
}
```

## Language File Structure - بنية ملفات اللغة

```json
{
    "messages": {
        "success": {
            "created": "Created successfully / تم الإنشاء بنجاح",
            "updated": "Updated successfully / تم التحديث بنجاح"
        },
        "errors": {
            "not_found": "Not found / غير موجود",
            "unauthorized": "Unauthorized / غير مصرح"
        }
    },
    "settings": {
        "created": "Setting created successfully / تم إنشاء الإعداد بنجاح"
    }
}
```

## Adding New Translations - إضافة ترجمات جديدة

1. Add the key to both `lang/ar.json` and `lang/en.json`
2. Use the key in your code: `__('your.key')`

1. أضف المفتاح في كل من `lang/ar.json` و `lang/en.json`
2. استخدم المفتاح في الكود: `__('your.key')`

## Example - مثال

### Before - قبل:
```php
return response()->json([
    'success' => false,
    'message' => 'Setting not found.',
], 404);
```

### After - بعد:
```php
return $this->notFoundResponse('settings.not_found');
```

Or - أو:
```php
return $this->errorResponse('settings.not_found', 404);
```

## Testing - الاختبار

### Using Postman - استخدام Postman

1. **Header Method:**
   ```
   Accept-Language: ar
   ```

2. **Query Parameter Method:**
   ```
   GET /api/v1/settings?lang=ar
   ```

3. **Cookie Method:**
   ```
   Cookie: locale=ar
   ```

## Migration Checklist - قائمة التحقق للترحيل

For each controller, update:
- [ ] Add `use HasLocalizedResponse;` trait
- [ ] Replace `response()->json()` with `$this->successResponse()` or `$this->errorResponse()`
- [ ] Replace `abort()` with `$this->forbiddenResponse()` or `$this->unauthorizedResponse()`
- [ ] Add translation keys to `lang/ar.json` and `lang/en.json`
- [ ] Test with both languages

لكل controller، قم بالتحديث:
- [ ] أضف `use HasLocalizedResponse;` trait
- [ ] استبدل `response()->json()` بـ `$this->successResponse()` أو `$this->errorResponse()`
- [ ] استبدل `abort()` بـ `$this->forbiddenResponse()` أو `$this->unauthorizedResponse()`
- [ ] أضف مفاتيح الترجمة في `lang/ar.json` و `lang/en.json`
- [ ] اختبر مع كلا اللغتين

