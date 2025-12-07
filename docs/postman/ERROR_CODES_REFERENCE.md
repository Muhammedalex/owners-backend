# Error Codes Reference - Auth API V1

## Quick Reference

| HTTP Code | Status | Common Scenarios |
|-----------|--------|------------------|
| 200 | OK | Successful request |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid parameters, expired tokens |
| 401 | Unauthorized | Missing/invalid token, wrong credentials |
| 422 | Unprocessable Entity | Validation errors, rate limiting |
| 500 | Internal Server Error | Server errors |

---

## Detailed Error Reference

### 400 - Bad Request

**When it occurs:**
- Invalid verification link
- Invalid password reset token
- Malformed request data

**Example Response:**
```json
{
    "success": false,
    "message": "Invalid verification link."
}
```

**Common Causes:**
- Expired verification link
- Invalid hash parameter
- Token already used

**Solutions:**
- Request new verification email
- Check link expiration
- Verify URL parameters

---

### 401 - Unauthorized

**When it occurs:**
- Missing authentication token
- Invalid/expired access token
- Wrong credentials
- Account deactivated

**Example Responses:**

**Missing Token:**
```json
{
    "message": "Unauthenticated."
}
```

**Invalid Credentials:**
```json
{
    "success": false,
    "message": "Invalid credentials."
}
```

**Account Deactivated:**
```json
{
    "success": false,
    "message": "Your account has been deactivated."
}
```

**Invalid Refresh Token:**
```json
{
    "success": false,
    "message": "Invalid or expired refresh token."
}
```

**Common Causes:**
- Token not provided in Authorization header
- Token expired (use refresh token)
- Wrong email/phone or password
- Account inactive
- Refresh token expired

**Solutions:**
- Include `Authorization: Bearer {token}` header
- Use Refresh Token endpoint
- Verify credentials
- Contact admin to activate account
- Request new tokens via login

---

### 422 - Unprocessable Entity

**When it occurs:**
- Validation errors
- Rate limiting
- Business rule violations

**Example Responses:**

**Validation Errors:**
```json
{
    "message": "The email field must be a valid email address. (and 2 more errors)",
    "errors": {
        "email": ["The email field must be a valid email address."],
        "password": [
            "The password field must be at least 8 characters.",
            "The password confirmation does not match."
        ]
    }
}
```

**Email Already Exists:**
```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": ["This email is already registered."]
    }
}
```

**Rate Limiting:**
```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "email": ["Too many login attempts. Please try again in 45 seconds."]
    }
}
```

**Common Validation Errors:**

| Field | Error | Solution |
|-------|-------|----------|
| email | Required | Provide email address |
| email | Invalid format | Use valid email format |
| email | Already taken | Use different email |
| email | Too many attempts | Wait before retrying |
| password | Required | Provide password |
| password | Too short | Minimum 8 characters |
| password | Confirmation mismatch | Passwords must match |
| phone | Already taken | Use different phone |
| phone | Invalid format | Use valid phone format |

**Rate Limiting:**
- **Limit**: 5 attempts per minute per identifier
- **Lockout**: 60 seconds
- **Solution**: Wait for lockout period to expire

---

### 500 - Internal Server Error

**When it occurs:**
- Server-side errors
- Database errors
- Unexpected exceptions

**Example Response:**
```json
{
    "success": false,
    "message": "Registration failed.",
    "error": "Database connection error"
}
```

**Common Causes:**
- Server configuration issues
- Database connectivity problems
- Code exceptions

**Solutions:**
- Retry request
- Check server status
- Contact support if persistent

---

## Field-Specific Errors

### Email Field

| Error | Cause | Solution |
|-------|-------|----------|
| Required | Email missing | Provide email |
| Invalid format | Wrong format | Use valid email |
| Already taken | Email exists | Use different email |
| Too many attempts | Rate limit | Wait and retry |

### Password Field

| Error | Cause | Solution |
|-------|-------|----------|
| Required | Password missing | Provide password |
| Too short | Less than 8 chars | Minimum 8 characters |
| Confirmation mismatch | Don't match | Ensure passwords match |

### Phone Field

| Error | Cause | Solution |
|-------|-------|----------|
| Already taken | Phone exists | Use different phone |
| Invalid format | Wrong format | Use valid format |

---

## Error Handling Best Practices

### 1. Check Status Code First

```javascript
if (response.status === 401) {
    // Handle authentication error
    // Refresh token or redirect to login
} else if (response.status === 422) {
    // Handle validation errors
    // Display field-specific errors
} else if (response.status === 500) {
    // Handle server error
    // Show generic error message
}
```

### 2. Parse Error Messages

```javascript
const error = response.data;
if (error.errors) {
    // Validation errors - show field-specific messages
    Object.keys(error.errors).forEach(field => {
        console.error(`${field}: ${error.errors[field][0]}`);
    });
} else {
    // General error message
    console.error(error.message);
}
```

### 3. Handle Token Expiration

```javascript
if (response.status === 401 && response.data.message === "Unauthenticated.") {
    // Token expired - refresh it
    refreshToken().then(() => {
        // Retry original request
    });
}
```

### 4. Rate Limiting Handling

```javascript
if (response.status === 422 && error.errors?.email?.[0]?.includes("Too many attempts")) {
    // Extract wait time from message
    const waitTime = extractWaitTime(error.errors.email[0]);
    // Show countdown to user
    showRateLimitMessage(waitTime);
}
```

---

## Common Error Scenarios

### Scenario 1: Login After Registration

**Problem:** User registers but can't login immediately

**Possible Causes:**
- Account not activated
- Email not verified
- Wrong credentials

**Solution:**
- Check account status
- Verify email
- Use correct credentials

### Scenario 2: Token Expired

**Problem:** Getting 401 errors on protected endpoints

**Possible Causes:**
- Access token expired (60 minutes)
- Token not included in request
- Token invalid

**Solution:**
- Use Refresh Token endpoint
- Include token in Authorization header
- Re-login if refresh token expired

### Scenario 3: Rate Limited

**Problem:** Can't login after multiple attempts

**Possible Causes:**
- Exceeded 5 attempts per minute
- Account temporarily locked

**Solution:**
- Wait 60 seconds
- Verify credentials before retrying
- Use correct email/phone

### Scenario 4: Validation Errors

**Problem:** Registration/login fails with 422

**Possible Causes:**
- Missing required fields
- Invalid field formats
- Duplicate email/phone

**Solution:**
- Check all required fields present
- Verify field formats
- Use unique email/phone

---

## Error Response Structure

### Validation Errors (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": [
            "Error message 1",
            "Error message 2"
        ]
    }
}
```

### General Errors

```json
{
    "success": false,
    "message": "Error description"
}
```

### Errors with Details

```json
{
    "success": false,
    "message": "Error description",
    "error": "Detailed error information"
}
```

---

## Debugging Tips

1. **Check Response Status** - Always check HTTP status code first
2. **Read Error Messages** - Error messages are descriptive
3. **Check Validation Errors** - Field-specific errors in `errors` object
4. **Verify Token Status** - Check if token is valid and not expired
5. **Review Request Format** - Ensure request matches API specification
6. **Check Account Status** - Verify account is active and verified
7. **Monitor Rate Limits** - Be aware of rate limiting restrictions

---

## Support

If you encounter errors not covered here:
1. Check the error message for details
2. Review API documentation
3. Verify request format
4. Check server logs (if accessible)
5. Contact support with error details

