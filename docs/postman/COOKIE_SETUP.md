# Postman Cookie Setup Guide

## Overview

The authentication API now uses httpOnly cookies for refresh tokens. This guide explains how to work with cookies in Postman.

---

## Cookie Management in Postman

### Automatic Cookie Handling

Postman automatically manages cookies for you:
- Cookies are automatically sent with requests
- Cookies are automatically stored when received in responses
- No manual cookie management needed

### Viewing Cookies

1. **In Request Builder:**
   - Click the "Cookies" link below the URL bar
   - View all cookies for the current domain
   - Manually add/edit/delete cookies if needed

2. **In Response:**
   - Check the "Cookies" tab in the response panel
   - See all cookies set by the server

---

## Testing Authentication Flow

### 1. Login/Register

**Request:**
```json
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
- `access_token` in JSON body (stored in environment variable)
- `refresh_token` in httpOnly cookie (automatically stored by Postman)

**What Happens:**
- Access token is saved to `{{access_token}}` environment variable
- Refresh token cookie is automatically stored by Postman
- Cookie is automatically sent with subsequent requests

---

### 2. Refresh Token

**Request:**
```json
POST /api/v1/auth/refresh
{}
```

**Important:**
- Empty body (refresh token comes from cookie)
- Cookie is automatically sent by Postman
- No need to manually add refresh_token to request

**Response:**
- New `access_token` in JSON body
- New `refresh_token` cookie (automatically updated)

**What Happens:**
- New access token saved to `{{access_token}}` environment variable
- Old refresh token cookie is replaced with new one
- Cookie is automatically updated by Postman

---

### 3. Authenticated Requests

**Request:**
```
GET /api/v1/auth/me
Headers:
  Authorization: Bearer {{access_token}}
```

**What Happens:**
- Access token from environment variable is used
- Refresh token cookie is automatically sent (if needed)
- No manual cookie management required

---

### 4. Logout

**Request:**
```json
POST /api/v1/auth/logout
Headers:
  Authorization: Bearer {{access_token}}
Body: {}
```

**What Happens:**
- Access token is validated
- Refresh token from cookie is revoked
- Refresh token cookie is cleared
- Access token environment variable should be cleared manually

---

## Troubleshooting

### Cookies Not Being Sent

**Problem:** Cookies are not being sent with requests

**Solutions:**
1. Check Postman Settings:
   - File → Settings → General
   - Ensure "Automatically follow redirects" is enabled
   - Ensure cookies are enabled

2. Check Cookie Domain:
   - Cookies are domain-specific
   - Ensure you're using the same domain for all requests
   - Check cookie domain in "Cookies" view

3. Check Cookie Path:
   - Cookies are path-specific
   - Ensure cookie path matches request path

### Refresh Token Not Found

**Problem:** Getting "Refresh token not found" error

**Solutions:**
1. Ensure you've logged in first:
   - Login/Register sets the refresh token cookie
   - Cookie must be set before refresh endpoint can work

2. Check Cookie Expiry:
   - Refresh token cookie expires in 30 days
   - If expired, login again to get new cookie

3. Check Cookie Domain:
   - Ensure cookie domain matches API domain
   - Check in "Cookies" view

### Access Token Not Persisting

**Problem:** Access token is lost after refresh

**Solution:**
- This is expected behavior
- Access token is stored in environment variable
- Environment variable persists across requests
- If lost, use refresh endpoint to get new token

---

## Best Practices

1. **Use Environment Variables:**
   - Store `access_token` in environment variable
   - Use `{{access_token}}` in Authorization header
   - Automatically updated after login/refresh

2. **Let Postman Handle Cookies:**
   - Don't manually manage cookies
   - Postman handles cookie storage and sending automatically
   - Trust the automatic cookie management

3. **Check Cookie Status:**
   - Use "Cookies" view to verify cookies are set
   - Check cookie expiry dates
   - Verify cookie domain and path

4. **Test Cookie Flow:**
   - Login → Check cookie is set
   - Refresh → Check cookie is updated
   - Logout → Check cookie is cleared

---

## Cookie Details

### Refresh Token Cookie

- **Name:** `refresh_token`
- **Type:** httpOnly (not accessible via JavaScript)
- **Secure:** true (HTTPS only in production)
- **SameSite:** Lax (CSRF protection)
- **Expiry:** 30 days
- **Path:** `/`

### Viewing Cookie Details

1. Click "Cookies" link below URL bar
2. Find `refresh_token` cookie
3. View:
   - Domain
   - Path
   - Expires
   - HttpOnly flag
   - Secure flag
   - SameSite attribute

---

## Environment Variables

### Required Variables

- `base_url`: API base URL (e.g., `http://localhost:8000`)
- `access_token`: Access token (automatically set after login/refresh)

### Removed Variables

- `refresh_token`: No longer needed (stored in cookie)

---

## Example Workflow

1. **Set Environment:**
   ```
   base_url = http://localhost:8000
   ```

2. **Login:**
   ```
   POST {{base_url}}/api/v1/auth/login
   → Access token saved to {{access_token}}
   → Refresh token saved to cookie
   ```

3. **Use Access Token:**
   ```
   GET {{base_url}}/api/v1/auth/me
   Authorization: Bearer {{access_token}}
   → Cookie automatically sent
   ```

4. **Refresh Token:**
   ```
   POST {{base_url}}/api/v1/auth/refresh
   Body: {}
   → New access token saved to {{access_token}}
   → New refresh token cookie set
   ```

5. **Logout:**
   ```
   POST {{base_url}}/api/v1/auth/logout
   Authorization: Bearer {{access_token}}
   → Cookie cleared
   → Clear {{access_token}} manually
   ```

---

## Notes

- Cookies work automatically in Postman
- No need to manually set cookies
- Refresh token is never in JSON response (only in cookie)
- Access token is always in JSON response
- Cookie management is transparent to the user

