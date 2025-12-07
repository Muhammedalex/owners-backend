# Token Storage Strategy

## Overview

This document explains the secure token storage strategy used in the application.

---

## Storage Strategy

### Access Token
- **Storage:** In-memory JavaScript variable
- **Accessibility:** Accessible via JavaScript
- **Security:** Not persisted to localStorage/sessionStorage
- **Lifetime:** Short-lived (60 minutes default)
- **Purpose:** Used for API authentication

### Refresh Token
- **Storage:** httpOnly cookie
- **Accessibility:** NOT accessible via JavaScript (httpOnly)
- **Security:** Protected from XSS attacks
- **Lifetime:** Long-lived (30 days default)
- **Purpose:** Used to refresh access tokens

---

## Why This Approach?

### Security Benefits

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
   - Additional CSRF token can be added if needed

---

## Implementation

### Backend (Laravel)

```php
// Set refresh token in httpOnly cookie
$cookie = cookie(
    'refresh_token',
    $refreshToken,
    $expiryMinutes,
    '/',
    null,
    true,  // secure (HTTPS only)
    true,  // httpOnly (not accessible via JS)
    false, // raw
    'lax'  // sameSite
);

return response()->json($data)->cookie($cookie);
```

### Frontend (React)

```javascript
// Access token stored in memory
let accessToken = null;

// Set access token after login
const setAccessToken = (token) => {
  accessToken = token;
};

// Axios configuration
const apiClient = axios.create({
  withCredentials: true, // Send cookies with requests
});
```

---

## API Response Structure

### Login/Register Response

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

**Note:** `refresh_token` is NOT in the JSON response. It's automatically set as an httpOnly cookie.

---

## Token Refresh Flow

1. **Access Token Expires**
   - API returns 401 Unauthorized

2. **Automatic Refresh**
   - Interceptor catches 401
   - Sends POST to `/auth/refresh` with empty body
   - Refresh token automatically sent via cookie

3. **New Tokens**
   - Server validates refresh token from cookie
   - Returns new access token in JSON
   - Sets new refresh token in cookie

4. **Retry Request**
   - Original request retried with new access token

---

## Logout Flow

1. **User Logs Out**
   - POST to `/auth/logout` with empty body
   - Refresh token automatically sent via cookie

2. **Server Response**
   - Revokes refresh token
   - Clears refresh token cookie
   - Returns success response

3. **Frontend Cleanup**
   - Clears access token from memory
   - Clears user state
   - Redirects to login

---

## Best Practices

1. **Always Use `withCredentials: true`**
   - Required for cookies to be sent with requests

2. **Never Store Access Token in localStorage**
   - Keep it in memory only

3. **Handle Token Refresh Automatically**
   - Use axios interceptors
   - Retry failed requests after refresh

4. **Clear Tokens on Logout**
   - Clear access token from memory
   - Server clears refresh token cookie

5. **Handle Refresh Failures**
   - Redirect to login if refresh fails
   - Clear all state

---

## Security Considerations

### HTTPS Required
- Cookies with `secure: true` only work over HTTPS
- Always use HTTPS in production

### SameSite Attribute
- Set to `lax` for better security
- Prevents CSRF attacks

### Cookie Expiry
- Match cookie expiry with refresh token expiry
- Automatically cleared when expired

### Token Rotation
- Refresh tokens rotate on each use
- Old tokens are invalidated
- Enhanced security

---

## Troubleshooting

### Cookies Not Sent
- Check `withCredentials: true` in axios config
- Verify CORS allows credentials
- Check cookie domain/path settings

### Refresh Token Not Found
- Verify cookie is set correctly
- Check cookie expiry
- Verify cookie name matches

### Access Token Not Persisted
- This is intentional - access token is in memory
- Will be lost on page refresh
- Use refresh token to get new access token

---

## Migration Notes

If migrating from localStorage-based tokens:

1. Remove localStorage token storage
2. Update axios config to use `withCredentials: true`
3. Update token refresh to not send refresh_token in body
4. Update logout to not send refresh_token in body
5. Handle access token in memory only

