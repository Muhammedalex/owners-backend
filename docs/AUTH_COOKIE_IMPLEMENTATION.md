# Auth Cookie Implementation - Summary

## Changes Made

### 1. Token Storage Strategy

**Before:**
- Access token: localStorage/sessionStorage
- Refresh token: localStorage/sessionStorage (accessible via JS)

**After:**
- Access token: In-memory JavaScript variable (NOT in localStorage)
- Refresh token: httpOnly cookie (NOT accessible via JS)

---

## Backend Changes

### AuthController Updates

1. **Login/Register Endpoints**
   - Set refresh token in httpOnly cookie
   - Return only access_token in JSON response (not refresh_token)
   - Cookie settings:
     - `httpOnly: true` - Not accessible via JavaScript
     - `secure: true` - HTTPS only
     - `sameSite: 'lax'` - CSRF protection

2. **Refresh Endpoint**
   - Read refresh token from cookie (not request body)
   - Validate refresh token from cookie
   - Set new refresh token in cookie after refresh
   - Return only access_token in JSON response

3. **Logout Endpoints**
   - Read refresh token from cookie
   - Clear refresh token cookie after logout

### RefreshTokenRequest Updates

- Removed `refresh_token` from validation rules
- Added custom validator to check cookie instead

---

## Frontend Changes Required

### 1. Axios Configuration

```javascript
const apiClient = axios.create({
  withCredentials: true, // CRITICAL: Send cookies with requests
});
```

### 2. Access Token Storage

```javascript
// Store in memory, NOT localStorage
let accessToken = null;

const setAccessToken = (token) => {
  accessToken = token;
};
```

### 3. Token Refresh

```javascript
// Refresh endpoint - empty body, cookie sent automatically
const response = await axios.post(
  '/auth/refresh',
  {}, // Empty body
  { withCredentials: true }
);
```

### 4. Login/Register Response

```javascript
// Response structure changed
{
  "data": {
    "tokens": {
      "access_token": "...", // Only this in JSON
      "token_type": "Bearer",
      "expires_in": 3600
    }
    // refresh_token is NOT in response - it's in cookie
  }
}
```

---

## API Response Structure

### Login Response

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": { ... },
    "tokens": {
      "access_token": "2|...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  }
}
```

**Set-Cookie Header:**
```
Set-Cookie: refresh_token=...; HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age=2592000
```

### Refresh Response

```json
{
  "success": true,
  "message": "Token refreshed successfully.",
  "data": {
    "user": { ... },
    "tokens": {
      "access_token": "2|...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  }
}
```

**Set-Cookie Header:**
```
Set-Cookie: refresh_token=...; HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age=2592000
```

---

## Security Benefits

1. **XSS Protection**
   - Refresh token in httpOnly cookie cannot be accessed by JavaScript
   - Even if XSS attack occurs, refresh token is safe

2. **No Persistent Storage**
   - Access token not stored in localStorage/sessionStorage
   - Reduces risk of token theft

3. **Automatic Cookie Handling**
   - Browser automatically sends cookies with requests
   - No manual token management needed

4. **CSRF Protection**
   - SameSite cookie attribute prevents CSRF attacks

---

## Testing the Refresh Token Fix

### Issue
You were getting: `"Invalid or expired refresh token."`

### Root Cause
The refresh endpoint was expecting `refresh_token` in the request body, but it should come from the cookie.

### Solution
1. Updated `AuthController::refresh()` to read from cookie
2. Updated `RefreshTokenRequest` to validate cookie
3. Cookie is automatically sent by browser with `withCredentials: true`

### Testing Steps

1. **Login**
   ```bash
   POST /api/v1/auth/login
   Body: { "email": "...", "password": "..." }
   ```
   - Check response: Should have `access_token` in JSON
   - Check Set-Cookie header: Should have `refresh_token` cookie

2. **Refresh Token**
   ```bash
   POST /api/v1/auth/refresh
   Body: {} (empty)
   Headers: Cookie: refresh_token=...
   ```
   - Should return new `access_token`
   - Should set new `refresh_token` cookie

3. **Verify Cookie**
   - In browser DevTools → Application → Cookies
   - Should see `refresh_token` cookie
   - Should be httpOnly (not accessible via `document.cookie`)

---

## CORS Configuration

For cookies to work with cross-origin requests, ensure:

1. **Backend CORS allows credentials:**
   ```php
   // In bootstrap/app.php or CORS config
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->api(prepend: [
           \Illuminate\Http\Middleware\HandleCors::class,
       ]);
   })
   ```

2. **Frontend sends credentials:**
   ```javascript
   axios.create({
     withCredentials: true,
   });
   ```

3. **Sanctum stateful domains configured:**
   ```php
   // config/sanctum.php
   'stateful' => ['localhost:3000', 'your-frontend-domain.com'],
   ```

---

## Migration Checklist

- [x] Update AuthController to set cookies
- [x] Update refresh endpoint to read from cookie
- [x] Update logout to clear cookie
- [x] Update RefreshTokenRequest validation
- [x] Update frontend API integration docs
- [ ] Update frontend axios config (withCredentials: true)
- [ ] Update frontend token storage (memory only)
- [ ] Update frontend refresh logic (empty body)
- [ ] Test login flow
- [ ] Test refresh flow
- [ ] Test logout flow
- [ ] Verify cookies in browser DevTools

---

## Notes

- Refresh token cookie expires in 30 days (configurable)
- Access token expires in 60 minutes (configurable)
- On page refresh, access token is lost (by design)
- Use refresh token to get new access token automatically
- All cookie operations are handled automatically by browser

