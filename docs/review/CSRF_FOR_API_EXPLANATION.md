# CSRF Protection للـ API - هل محتاجينه؟

## 📋 الإجابة المختصرة

**للـ API اللي بيستخدم Bearer Tokens فقط:** ❌ **مش محتاج CSRF**

**للـ API اللي بيستخدم Cookies (مثلك):** ⚠️ **مش محتاج CSRF، بس محتاج SameSite='strict'**

---

## 🔍 شرح مفصل

### 1. CSRF Attack إيه؟

**CSRF (Cross-Site Request Forgery)** هو نوع من الهجمات:
- المهاجم بيخلي المستخدم يعمل request للـ API بدون مايعرف
- الـ Browser بيبعت الـ Cookies تلقائياً مع الـ Request
- لو الـ Request state-changing (مثلاً logout, delete, update)، ممكن يحصل ضرر

**مثال:**
```html
<!-- موقع خبيث -->
<img src="https://your-api.com/api/v1/auth/logout" />
<!-- المستخدم يفتح الصفحة دي، الـ Browser بيبعت الـ Cookie تلقائياً -->
```

---

## ✅ حمايتك الحالية

### 1. Bearer Token Authentication
```php
// Access token في Header (مش في Cookie)
Authorization: Bearer {access_token}
```
- ✅ **مش محتاج CSRF** - الـ Token مش في Cookie، فمش هيتبعت تلقائياً

### 2. Refresh Token في Cookie
```php
// Refresh token في httpOnly cookie
Cookie: refresh_token=...
```
- ⚠️ **ممكن يكون محتاج CSRF** - الـ Cookie هيتبعت تلقائياً

### 3. SameSite='lax' Cookie
```php
// في AuthService.php
'sameSite' => 'lax'
```
- ✅ **حماية جزئية** - بيحمي من معظم CSRF attacks
- ⚠️ **مش كامل** - SameSite='strict' أحسن

---

## 🎯 هل محتاجين CSRF؟

### السيناريو 1: API فقط + Bearer Tokens
```
Access Token: Bearer token (في Header)
Refresh Token: Cookie (httpOnly, SameSite='lax')
```
**النتيجة:** ⚠️ **مش محتاج CSRF، بس محتاج SameSite='strict'**

**ليه؟**
- الـ Access Token في Header (مش Cookie) → مش محتاج CSRF
- الـ Refresh Token في Cookie → محتاج حماية
- **SameSite='strict'** كافي لحماية الـ Refresh Token

---

### السيناريو 2: Stateful API (Sessions)
```
Authentication: Session Cookie
```
**النتيجة:** ✅ **محتاج CSRF**

**ليه؟**
- كل الـ Requests بتستخدم Cookies
- محتاج CSRF token لكل state-changing operation

---

## ✅ التوصية لك

### الحل الأفضل: SameSite='strict'

**بدل CSRF، استخدم SameSite='strict' للـ Refresh Token Cookie:**

```php
// في app/Services/V1/Auth/AuthService.php
public function createRefreshTokenCookie(string $refreshToken): Cookie
{
    return Cookie::create(
        'refresh_token',
        $refreshToken,
        $expiry->getTimestamp(),
        '/',
        null,
        config('app.env') === 'production', // secure
        true,  // httpOnly
        false, // raw
        'strict'  // sameSite - غير من 'lax' لـ 'strict'
    );
}
```

**ليه 'strict' أحسن من 'lax'؟**
- `'lax'`: الـ Cookie بيتبعت في GET requests من external sites
- `'strict'`: الـ Cookie **مش** بيتبعت من external sites خالص
- **أقوى حماية من CSRF**

---

## 🔒 متى محتاج CSRF فعلاً؟

### 1. لو عندك HTML Forms
```html
<form action="/api/v1/users/delete" method="POST">
    <!-- محتاج CSRF token هنا -->
</form>
```

### 2. لو الـ API stateful (Sessions)
```php
// لو بتستخدم Sessions للـ Authentication
Auth::login($user); // Session-based
```

### 3. لو الـ Frontend في نفس Domain
```php
// لو الـ Frontend والـ API في نفس domain
// وبتستخدم Cookies للـ Authentication
```

---

## 🛠️ لو عايز تضيف CSRF (اختياري)

### الطريقة 1: CSRF Token للـ Refresh Endpoint فقط

```php
// في routes/api/v1/auth.php
Route::post('/refresh', [AuthController::class, 'refresh'])
    ->middleware(['web', 'throttle:10,1']); // 'web' middleware group includes CSRF
```

**مش محبذ** - بيخلي الـ API stateful

---

### الطريقة 2: Custom CSRF للـ API

```php
// app/Http/Middleware/VerifyApiCsrfToken.php
<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyApiCsrfToken extends Middleware
{
    protected $except = [
        // Exclude all API routes except sensitive ones
        'api/v1/auth/refresh',
        'api/v1/auth/logout',
    ];
}
```

**مش محبذ** - معقد ومش محتاج

---

## ✅ التوصية النهائية

### الحل الموصى به:

1. ✅ **استخدم SameSite='strict'** للـ Refresh Token Cookie
2. ✅ **استخدم Bearer Tokens** للـ Access Token (مش Cookie)
3. ❌ **مش محتاج CSRF** - SameSite='strict' كافي

**الكود:**
```php
// في app/Services/V1/Auth/AuthService.php
'sameSite' => 'strict' // بدل 'lax'
```

---

## 📊 مقارنة

| الحماية | SameSite='lax' | SameSite='strict' | CSRF Token |
|---------|----------------|-------------------|------------|
| حماية من CSRF | جزئية | قوية | قوية جداً |
| سهولة التطبيق | ✅ سهل | ✅ سهل | ⚠️ معقد |
| مناسب للـ API | ✅ | ✅ | ❌ مش محبذ |
| Performance | ✅ ممتاز | ✅ ممتاز | ⚠️ overhead |

---

## 🎯 الخلاصة

### للـ API اللي بتعمله:

1. ✅ **Access Token في Header** (Bearer) → مش محتاج CSRF
2. ✅ **Refresh Token في Cookie** → محتاج SameSite='strict'
3. ❌ **مش محتاج CSRF Token** - SameSite='strict' كافي

### التغيير المطلوب:

```php
// غير SameSite من 'lax' لـ 'strict'
'sameSite' => 'strict'
```

**ده كافي لحمايتك من CSRF attacks!** ✅

---

## 🔗 مراجع

- [OWASP CSRF Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [MDN SameSite Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite)
- [Laravel CSRF Protection](https://laravel.com/docs/csrf)

