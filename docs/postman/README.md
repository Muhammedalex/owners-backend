# Postman Collection - Auth API V1

## Quick Start

### Import Collection

1. Open Postman
2. Click **Import** button
3. Select `AUTH_API_COLLECTION.json`
4. Collection will be imported with all endpoints

### Setup Environment

1. Create a new environment in Postman
2. Add these variables:

| Variable | Initial Value | Current Value |
|----------|---------------|---------------|
| `base_url` | `http://localhost:8000` | `http://localhost:8000` |
| `access_token` | (empty) | (auto-set) |
| `refresh_token` | (empty) | (auto-set) |
| `user_id` | (empty) | (auto-set) |

3. Select the environment before making requests

### Auto Token Management

The collection includes scripts that automatically:
- ✅ Save `access_token` after login/register
- ✅ Save `refresh_token` after login/register
- ✅ Save `user_id` after login/register
- ✅ Use tokens in Authorization headers

**No manual token management needed!**

---

## Collection Structure

```
Auth API V1
├── Public Endpoints
│   ├── Register
│   ├── Login
│   ├── Refresh Token
│   ├── Forgot Password
│   ├── Reset Password
│   └── Verify Email
└── Protected Endpoints
    ├── Get Current User
    ├── Logout
    ├── Logout All Devices
    └── Resend Verification Email
```

---

## Usage Flow

### 1. Register New User

1. Open **Register** request
2. Update request body with your data
3. Send request
4. Tokens are automatically saved to environment

### 2. Login

1. Open **Login** request
2. Update email/phone and password
3. Send request
4. Tokens are automatically saved

### 3. Use Protected Endpoints

1. Select your environment (with saved tokens)
2. Open any protected endpoint
3. Authorization header is automatically set
4. Send request

### 4. Refresh Token

1. When access token expires (401 error)
2. Open **Refresh Token** request
3. Refresh token is automatically used
4. New tokens are automatically saved

---

## Features

### ✅ Complete Documentation
- Detailed request descriptions
- Response examples
- Error scenarios
- Field explanations

### ✅ Auto Token Management
- Automatic token saving
- Automatic token usage
- Token refresh support

### ✅ Error Examples
- Success responses
- Validation errors
- Authentication errors
- Server errors

### ✅ Multiple Scenarios
- Email login
- Phone login
- Token refresh
- Error handling

---

## Testing Workflow

### Complete Authentication Flow

1. **Register** → Get tokens
2. **Verify Email** → Verify account
3. **Get Current User** → Test protected endpoint
4. **Refresh Token** → Test token refresh
5. **Logout** → Test logout

### Error Testing

Each endpoint includes error examples:
- Invalid credentials
- Validation errors
- Rate limiting
- Token expiration

---

## Documentation

For detailed API documentation, see:
- **[API_DOCUMENTATION.md](./API_DOCUMENTATION.md)** - Complete API reference
- **[AUTH_API_COLLECTION.json](./AUTH_API_COLLECTION.json)** - Postman collection

---

## Tips

1. **Use Environment Variables** - Set `base_url` for easy switching between dev/staging/prod
2. **Check Auto-Saved Tokens** - Tokens are automatically saved after login/register
3. **Test Error Scenarios** - Collection includes error response examples
4. **Use Pre-request Scripts** - Some requests auto-populate from environment
5. **Monitor Token Expiry** - Access tokens expire in 60 minutes

---

## Troubleshooting

### Tokens Not Saving

- Check environment is selected
- Verify Postman scripts are enabled
- Check console for errors

### 401 Unauthorized

- Verify token is saved in environment
- Check token hasn't expired
- Use Refresh Token endpoint

### 422 Validation Errors

- Check request body format
- Verify required fields are present
- Review error messages for details

---

## Support

For API issues:
1. Check error response messages
2. Review validation errors
3. Verify token status
4. Check account status (active, verified)

