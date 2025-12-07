# Troubleshooting Refresh Token Cookie Issues

## Error: "Refresh token is required"

This error means the refresh token cookie is not being sent with the request.

---

## Step-by-Step Troubleshooting

### 1. Verify You've Logged In First

**Important:** You must login/register BEFORE using the refresh endpoint.

1. **Login First:**
   ```
   POST /api/v1/auth/login
   {
     "email": "admin@owners.com",
     "password": "password"
   }
   ```

2. **Check Response:**
   - Should return `access_token` in JSON
   - Should set `refresh_token` cookie (check Set-Cookie header)

3. **Verify Cookie is Set:**
   - In Postman, click "Cookies" link below URL bar
   - Look for `refresh_token` cookie
   - Check cookie domain, path, and expiry

---

### 2. Check Postman Cookie Settings

**Enable Cookie Management:**

1. **File → Settings → General**
   - Ensure cookies are enabled
   - Check "Automatically follow redirects" if needed

2. **View Cookies:**
   - Click "Cookies" link below the URL bar in Postman
   - Should see `refresh_token` cookie after login
   - Cookie should have:
     - Domain: `localhost` (or your API domain)
     - Path: `/`
     - HttpOnly: Yes
     - Secure: No (for local development)

---

### 3. Verify Cookie Domain and Path

**Cookie Must Match Request Domain:**

- If API is `http://localhost:8000`
- Cookie domain should be `localhost` or empty
- Cookie path should be `/`

**Check in Postman:**
1. Click "Cookies" link
2. Find `refresh_token` cookie
3. Verify domain matches your API URL

---

### 4. Test Cookie is Being Sent

**Manual Check:**

1. **Login:**
   ```
   POST http://localhost:8000/api/v1/auth/login
   ```

2. **Check Response Headers:**
   - Look for `Set-Cookie` header
   - Should contain `refresh_token=...`

3. **Check Cookies:**
   - Click "Cookies" in Postman
   - Verify `refresh_token` is listed

4. **Refresh Request:**
   ```
   POST http://localhost:8000/api/v1/auth/refresh
   Body: {}
   ```

5. **Check Request:**
   - In Postman, go to "Headers" tab
   - Should see `Cookie: refresh_token=...` header
   - If not, cookie isn't being sent

---

### 5. Common Issues and Solutions

#### Issue: Cookie Not Set After Login

**Possible Causes:**
- Cookie domain doesn't match
- Cookie path doesn't match
- Cookie settings (secure, httpOnly) blocking it

**Solution:**
- Check `Set-Cookie` header in login response
- Verify cookie domain/path in Postman
- For local dev, ensure `secure: false` in cookie config

#### Issue: Cookie Not Sent with Refresh Request

**Possible Causes:**
- Cookie domain/path mismatch
- Cookie expired
- Postman not sending cookies

**Solution:**
- Verify cookie domain matches API domain
- Check cookie hasn't expired
- Ensure Postman cookie management is enabled
- Try manually adding cookie in Postman

#### Issue: Cookie Domain Mismatch

**Problem:**
- API: `http://localhost:8000`
- Cookie domain: `127.0.0.1`

**Solution:**
- Use consistent domain (always `localhost` or always `127.0.0.1`)
- Or set cookie domain to `null` (current domain)

---

## Quick Test Flow

1. **Clear All Cookies:**
   - In Postman: Click "Cookies" → Delete all cookies

2. **Login:**
   ```
   POST http://localhost:8000/api/v1/auth/login
   {
     "email": "admin@owners.com",
     "password": "password"
   }
   ```

3. **Verify Cookie:**
   - Check "Cookies" link
   - Should see `refresh_token` cookie

4. **Refresh:**
   ```
   POST http://localhost:8000/api/v1/auth/refresh
   Body: {}
   ```

5. **Should Work:**
   - Returns new `access_token`
   - Updates `refresh_token` cookie

---

## Manual Cookie Setup (If Needed)

If automatic cookie management isn't working:

1. **After Login:**
   - Copy `refresh_token` value from `Set-Cookie` header
   - Or get from response (if temporarily in JSON for testing)

2. **Manually Add Cookie:**
   - Click "Cookies" in Postman
   - Click "Add Cookie"
   - Set:
     - Name: `refresh_token`
     - Value: `[token value]`
     - Domain: `localhost`
     - Path: `/`
     - HttpOnly: Yes
     - Secure: No (for local)

3. **Test Refresh:**
   - Should now work with manually set cookie

---

## Debugging Tips

### Check Request Headers

In Postman, when making refresh request:
1. Go to "Headers" tab
2. Look for `Cookie` header
3. Should see: `Cookie: refresh_token=...`

If `Cookie` header is missing, cookie isn't being sent.

### Check Response Headers

After login:
1. Go to "Headers" tab in response
2. Look for `Set-Cookie` header
3. Should see: `Set-Cookie: refresh_token=...; HttpOnly; Path=/`

If `Set-Cookie` header is missing, cookie isn't being set.

### Check Cookie in Browser DevTools

If testing in browser:
1. Open DevTools → Application → Cookies
2. Should see `refresh_token` cookie
3. Check domain, path, httpOnly flags

---

## Still Not Working?

1. **Verify Backend Cookie Settings:**
   - Check `config/session.php`
   - Ensure cookie settings are correct for your environment

2. **Check CORS:**
   - Ensure CORS allows credentials
   - Check `withCredentials: true` in frontend

3. **Test with cURL:**
   ```bash
   # Login
   curl -X POST http://localhost:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@owners.com","password":"password"}' \
     -c cookies.txt
   
   # Refresh (uses cookies from file)
   curl -X POST http://localhost:8000/api/v1/auth/refresh \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -c cookies.txt
   ```

4. **Check Laravel Logs:**
   - Check `storage/logs/laravel.log`
   - Look for cookie-related errors

---

## Expected Behavior

✅ **Working:**
- Login sets `refresh_token` cookie
- Cookie visible in Postman "Cookies"
- Refresh request includes `Cookie` header
- Refresh returns new `access_token`
- Cookie is automatically updated

❌ **Not Working:**
- No cookie after login
- Cookie not sent with refresh request
- "Refresh token is required" error
- Cookie expired or invalid

