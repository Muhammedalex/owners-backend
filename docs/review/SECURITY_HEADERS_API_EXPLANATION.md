# Security Headers للـ API - شرح وتطبيق

## 📋 لماذا Security Headers مهمة حتى لو API فقط؟

حتى لو أنت بتعمل API فقط (مش HTML pages)، الـ Security Headers لسه مهمة لأن:

1. **الـ Frontend** اللي بيستخدم الـ API محتاج يحمي نفسه
2. **الـ Browser** بيقرأ الـ Headers دي من الـ API Response
3. **الحماية** بتطبق على كل الـ HTTP Responses (حتى JSON)
4. **Security Scanners** بتفحص الـ Headers دي

---

## 🔍 شرح كل Header

### 1. **X-Content-Type-Options: nosniff** ⭐⭐⭐ (مهم جداً للـ API)

**ليه مهم؟**
- بيخلي الـ Browser مايغيرش الـ Content-Type تلقائياً
- بيحمي من **MIME Sniffing Attacks**
- لو الـ API رجع JSON بس الـ Browser فكر إنه HTML، ممكن يحاول ينفذ كود JavaScript

**مثال على المشكلة:**
```
API Response: Content-Type: application/json
Body: {"data": "<script>alert('XSS')</script>"}
```
بدون الـ Header، الـ Browser ممكن يفكر إنه HTML وينفذ الـ Script!

**التطبيق:**
```php
'X-Content-Type-Options' => 'nosniff'
```

---

### 2. **Strict-Transport-Security (HSTS)** ⭐⭐⭐ (مهم جداً)

**ليه مهم؟**
- بيجبر الـ Browser يستخدم HTTPS فقط
- بيحمي من **Man-in-the-Middle Attacks**
- لو حد حاول يفتح الـ API على HTTP، الـ Browser هيحوله تلقائياً لـ HTTPS

**التطبيق:**
```php
'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
```
- `max-age=31536000` = سنة كاملة
- `includeSubDomains` = يطبق على كل الـ Subdomains

---

### 3. **X-Frame-Options** ⭐ (مش مهم للـ API)

**ليه مش مهم للـ API؟**
- بيحمي من **Clickjacking** (إخفاء الصفحة في iframe)
- بس الـ API مش بيطلع HTML، فمش محتاجينه
- **لكن** لو عندك أي endpoint بيرجع HTML (مثلاً error pages)، محتاجه

**التطبيق (اختياري):**
```php
'X-Frame-Options' => 'DENY' // أو 'SAMEORIGIN'
```

---

### 4. **Content-Security-Policy (CSP)** ⭐ (مش مهم للـ API)

**ليه مش مهم للـ API؟**
- بيحدد من فين الـ Browser يقدر يحمل Resources (JS, CSS, Images)
- بس الـ API مش بيطلع HTML، فمش محتاجينه
- **لكن** لو عندك أي endpoint بيرجع HTML، محتاجه

**التطبيق (اختياري):**
```php
'Content-Security-Policy' => "default-src 'self'"
```

---

### 5. **X-XSS-Protection** ⭐ (مش مهم للـ API)

**ليه مش مهم للـ API؟**
- بيخلي الـ Browser يحمي نفسه من XSS
- بس الـ API مش بيطلع HTML، فمش محتاجينه
- الـ Browser نفسه بيحمي نفسه

**التطبيق (اختياري):**
```php
'X-XSS-Protection' => '1; mode=block'
```

---

### 6. **Referrer-Policy** ⭐⭐ (مفيد)

**ليه مفيد؟**
- بيحدد إيه الـ Referrer اللي الـ Browser يبعتوه
- بيحمي من تسريب معلومات في الـ URLs
- مفيد لو الـ API فيه sensitive data في الـ URLs

**التطبيق:**
```php
'Referrer-Policy' => 'strict-origin-when-cross-origin'
```

---

## ✅ الـ Headers المهمة للـ API

### المهمة جداً (يجب تطبيقها):
1. ✅ **X-Content-Type-Options: nosniff**
2. ✅ **Strict-Transport-Security (HSTS)**

### المفيدة (يُنصح بها):
3. ⚠️ **Referrer-Policy**

### غير المهمة للـ API (اختياري):
4. ⚪ **X-Frame-Options** (مش محتاجينه)
5. ⚪ **Content-Security-Policy** (مش محتاجينه)
6. ⚪ **X-XSS-Protection** (مش محتاجينه)

---

## 🛠️ التطبيق في Laravel

### الطريقة 1: Middleware (الأفضل)

```php
// app/Http/Middleware/SecurityHeaders.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Headers المهمة للـ API
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // HSTS - بس في Production و HTTPS
        if (config('app.env') === 'production' && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }
        
        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

### الطريقة 2: في bootstrap/app.php (أبسط)

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
})
```

---

## 📝 ملخص

### للـ API فقط:
- ✅ **X-Content-Type-Options** - مهم جداً
- ✅ **HSTS** - مهم جداً (في Production)
- ⚠️ **Referrer-Policy** - مفيد

### مش محتاجينه للـ API:
- ❌ CSP
- ❌ X-Frame-Options
- ❌ X-XSS-Protection

---

## 🔗 مراجع

- [OWASP Security Headers](https://owasp.org/www-project-secure-headers/)
- [MDN Security Headers](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers#security)
- [Laravel Middleware](https://laravel.com/docs/middleware)

