# Debugging Cookie Issue

## Problem
Refresh token cookie is not being set or received.

## Steps to Debug

### 1. Check Login Response Headers

After login, check the response headers in Postman:

1. **Go to "Headers" tab in response**
2. **Look for `Set-Cookie` header**
3. **Should see something like:**
   ```
   Set-Cookie: refresh_token=abc123...; Path=/; HttpOnly; SameSite=Lax
   ```

**If `Set-Cookie` header is missing:**
- Cookie is not being set by server
- Check Laravel logs: `storage/logs/laravel.log`
- Check if cookie helper is working

**If `Set-Cookie` header is present:**
- Cookie is being set, but might not be stored by Postman
- Check Postman cookie settings

---

### 2. Check Postman Cookie Storage

1. **Click "Cookies" link below URL bar**
2. **Look for `refresh_token` cookie**
3. **Check:**
   - Domain: Should be `localhost` or empty
   - Path: Should be `/`
   - HttpOnly: Should be checked
   - Secure: Should be unchecked (for HTTP)

**If cookie is not listed:**
- Postman is not storing the cookie
- Check Postman settings
- Try manually adding cookie

---

### 3. Check Refresh Request Headers

When making refresh request:

1. **Go to "Headers" tab in request**
2. **Look for `Cookie` header**
3. **Should see:**
   ```
   Cookie: refresh_token=abc123...
   ```

**If `Cookie` header is missing:**
- Postman is not sending the cookie
- Check Postman cookie settings
- Verify cookie domain/path matches

---

### 4. Manual Cookie Test

Try manually adding the cookie:

1. **After login, copy the refresh_token value** (from response if temporarily included, or from Set-Cookie header)
2. **Click "Cookies" in Postman**
3. **Click "Add Cookie"**
4. **Set:**
   - Name: `refresh_token`
   - Value: `[paste token value]`
   - Domain: `localhost`
   - Path: `/`
   - HttpOnly: Yes
   - Secure: No
5. **Save and try refresh again**

---

### 5. Check Laravel Logs

Check `storage/logs/laravel.log` for debug messages:

```bash
tail -f storage/logs/laravel.log
```

Look for:
- "Login - Setting cookie:" - Confirms cookie is being set
- "Refresh endpoint - All cookies:" - Shows what cookies are received

---

### 6. Test with cURL

Test if cookies work with cURL:

```bash
# Login and save cookies
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@owners.com","password":"password"}' \
  -c cookies.txt \
  -v

# Check cookies.txt file
cat cookies.txt

# Refresh using saved cookies
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -c cookies.txt \
  -v
```

**If cURL works but Postman doesn't:**
- Postman cookie handling issue
- Check Postman settings

**If cURL doesn't work:**
- Server-side cookie issue
- Check Laravel configuration

---

### 7. Common Issues

#### Issue: Cookie Domain Mismatch

**Problem:**
- API: `http://localhost:8000`
- Cookie domain: `127.0.0.1`

**Solution:**
- Use consistent domain
- Set cookie domain to `null` (current domain)

#### Issue: Cookie Path Mismatch

**Problem:**
- Cookie path: `/api`
- Request path: `/api/v1/auth/refresh`

**Solution:**
- Set cookie path to `/` (root)

#### Issue: Secure Flag

**Problem:**
- Cookie has `Secure` flag
- Using HTTP (not HTTPS)

**Solution:**
- Set `secure: false` for local development

#### Issue: SameSite None

**Problem:**
- Cookie has `SameSite=None`
- Not using HTTPS

**Solution:**
- Use `SameSite=Lax` for local development

---

### 8. Postman Settings

Ensure Postman is configured correctly:

1. **File → Settings → General**
   - ✅ Automatically follow redirects
   - ✅ Send cookies automatically

2. **File → Settings → Privacy**
   - ✅ Allow sending cookies

3. **Restart Postman** after changing settings

---

### 9. Temporary Debug Endpoint

Add this to test cookie reading:

```php
// In AuthController - REMOVE AFTER TESTING
public function testCookie(Request $request)
{
    return response()->json([
        'cookies' => $request->cookies->all(),
        'cookie_header' => $request->header('Cookie'),
        'refresh_token' => $request->cookie('refresh_token'),
    ]);
}
```

Test:
```
GET /api/v1/auth/test-cookie
```

---

## Expected Behavior

✅ **Working:**
1. Login sets `Set-Cookie` header
2. Cookie appears in Postman "Cookies"
3. Refresh request includes `Cookie` header
4. Refresh endpoint receives cookie

❌ **Not Working:**
1. No `Set-Cookie` header in login response
2. Cookie not in Postman "Cookies"
3. No `Cookie` header in refresh request
4. Refresh endpoint doesn't receive cookie

---

## Quick Fix Checklist

- [ ] Check `Set-Cookie` header in login response
- [ ] Check cookie in Postman "Cookies"
- [ ] Check `Cookie` header in refresh request
- [ ] Verify cookie domain/path
- [ ] Check Laravel logs
- [ ] Test with cURL
- [ ] Check Postman settings
- [ ] Restart Postman

