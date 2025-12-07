# API Integration Guide

## Overview

Complete guide for integrating the React frontend with the Laravel API.

---

## API Client Setup

### Axios Instance

**Location:** `api/client.js`

```javascript
import axios from 'axios';

// Access token stored in memory (not localStorage/sessionStorage)
let accessToken = null;

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Important: Send cookies with requests
});

// Request interceptor - Add access token
apiClient.interceptors.request.use(
  (config) => {
    if (accessToken) {
      config.headers.Authorization = `Bearer ${accessToken}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - Handle token refresh
apiClient.interceptors.response.use(
  (response) => {
    // Store access token from response if present
    if (response.data?.data?.tokens?.access_token) {
      accessToken = response.data.data.tokens.access_token;
    }
    return response;
  },
  async (error) => {
    const originalRequest = error.config;

    // If 401 and not already retried
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        // Refresh token is automatically sent via cookie (httpOnly)
        // No need to manually send it in request body
        const response = await axios.post(
          `${apiClient.defaults.baseURL}/auth/refresh`,
          {}, // Empty body - refresh token comes from cookie
          { withCredentials: true } // Ensure cookies are sent
        );

        // Update access token in memory
        accessToken = response.data.data.tokens.access_token;
        // Refresh token is automatically updated in cookie by server

        // Retry original request
        originalRequest.headers.Authorization = `Bearer ${accessToken}`;
        return apiClient(originalRequest);
      } catch (refreshError) {
        // Refresh failed - clear access token and redirect to login
        accessToken = null;
        window.location.href = '/auth/login';
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);

// Export function to set access token (used after login/register)
export const setAccessToken = (token) => {
  accessToken = token;
};

// Export function to clear access token (used on logout)
export const clearAccessToken = () => {
  accessToken = null;
};

// Export function to get access token
export const getAccessToken = () => accessToken;

export default apiClient;
```

---

## API Endpoints

### Endpoint Constants

**Location:** `api/endpoints.js`

```javascript
export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/auth/login',
    REGISTER: '/auth/register',
    LOGOUT: '/auth/logout',
    LOGOUT_ALL: '/auth/logout-all',
    REFRESH: '/auth/refresh',
    ME: '/auth/me',
    VERIFY_EMAIL: '/auth/verify-email',
    RESEND_VERIFICATION: '/auth/resend-verification',
    FORGOT_PASSWORD: '/auth/forgot-password',
    RESET_PASSWORD: '/auth/reset-password',
  },
};
```

---

## Auth API Functions

**Location:** `api/v1/auth/auth.api.js`

```javascript
import apiClient from '@/api/client';
import { API_ENDPOINTS } from '@/api/endpoints';

export const authApi = {
  /**
   * Login user
   */
  async login(credentials) {
    const response = await apiClient.post(
      API_ENDPOINTS.AUTH.LOGIN,
      credentials
    );
    return response.data;
  },

  /**
   * Register new user
   */
  async register(data) {
    const response = await apiClient.post(
      API_ENDPOINTS.AUTH.REGISTER,
      data
    );
    return response.data;
  },

  /**
   * Logout user
   * Note: Refresh token is automatically sent via httpOnly cookie
   */
  async logout() {
    await apiClient.post(
      API_ENDPOINTS.AUTH.LOGOUT,
      {}, // Empty body - refresh token comes from cookie
      { withCredentials: true }
    );
  },

  /**
   * Logout from all devices
   */
  async logoutAll() {
    await apiClient.post(API_ENDPOINTS.AUTH.LOGOUT_ALL);
  },

  /**
   * Refresh access token
   * Note: Refresh token is automatically sent via httpOnly cookie
   */
  async refreshToken() {
    const response = await apiClient.post(
      API_ENDPOINTS.AUTH.REFRESH,
      {}, // Empty body - refresh token comes from cookie
      { withCredentials: true }
    );
    return response.data.data.tokens;
  },

  /**
   * Get current user
   */
  async getCurrentUser() {
    const response = await apiClient.get(API_ENDPOINTS.AUTH.ME);
    return response.data.data;
  },

  /**
   * Verify email
   */
  async verifyEmail(id, hash) {
    await apiClient.get(
      `${API_ENDPOINTS.AUTH.VERIFY_EMAIL}/${id}/${hash}`
    );
  },

  /**
   * Resend verification email
   */
  async resendVerification() {
    await apiClient.post(API_ENDPOINTS.AUTH.RESEND_VERIFICATION);
  },

  /**
   * Request password reset
   */
  async forgotPassword(email) {
    await apiClient.post(API_ENDPOINTS.AUTH.FORGOT_PASSWORD, { email });
  },

  /**
   * Reset password
   */
  async resetPassword(data) {
    await apiClient.post(API_ENDPOINTS.AUTH.RESET_PASSWORD, data);
  },
};
```

---

## React Query Integration

### Auth Queries

**Location:** `api/v1/auth/queries.js`

```javascript
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { authApi } from './auth.api';
import { tokenService } from '@/features/auth/services/token.service';
import { useAuthStore } from '@/features/auth/store/auth.store';
import { usePermissionStore } from '@/features/auth/store/permission.store';

// Get current user query
export const useCurrentUser = () => {
  return useQuery({
    queryKey: ['auth', 'user'],
    queryFn: () => authApi.getCurrentUser(),
    enabled: !!tokenService.getAccessToken(),
    retry: false,
  });
};

// Login mutation
export const useLogin = () => {
  const queryClient = useQueryClient();
  const setUser = useAuthStore(state => state.setUser);
  const setAccessToken = useAuthStore(state => state.setAccessToken);
  const setPermissions = usePermissionStore(state => state.setPermissions);
  const setRoles = usePermissionStore(state => state.setRoles);
  const { setAccessToken: setApiAccessToken } = require('@/api/client');

  return useMutation({
    mutationFn: authApi.login,
    onSuccess: (data) => {
      const { user, tokens } = data.data;
      
      // Store access token in memory (refresh token is in httpOnly cookie)
      setApiAccessToken(tokens.access_token);
      setAccessToken(tokens.access_token);
      
      // Store user
      setUser(user);
      queryClient.setQueryData(['auth', 'user'], user);
      
      // Store permissions
      setPermissions(user.permissions || []);
      setRoles(user.roles || []);
    },
  });
};

// Register mutation
export const useRegister = () => {
  const queryClient = useQueryClient();
  const setUser = useAuthStore(state => state.setUser);
  const setTokens = useAuthStore(state => state.setTokens);

  return useMutation({
    mutationFn: authApi.register,
    onSuccess: (data) => {
      const { user, tokens } = data.data;
      
      tokenService.setTokens(tokens);
      setTokens(tokens);
      setUser(user);
      queryClient.setQueryData(['auth', 'user'], user);
    },
  });
};

// Logout mutation
export const useLogout = () => {
  const queryClient = useQueryClient();
  const clearAuth = useAuthStore(state => state.clearAuth);
  const clearPermissions = usePermissionStore(state => state.clearPermissions);
  const { clearAccessToken } = require('@/api/client');

  return useMutation({
    mutationFn: authApi.logout,
    onSuccess: () => {
      // Clear access token from memory
      clearAccessToken();
      // Refresh token cookie is automatically cleared by server
      clearAuth();
      clearPermissions();
      queryClient.clear();
    },
  });
};
```

---

## Error Handling

### Error Handler

```javascript
// Error handler utility
export const handleApiError = (error) => {
  if (error.response) {
    return {
      message: error.response.data.message || 'An error occurred',
      errors: error.response.data.errors,
      status: error.response.status,
    };
  }
  
  return {
    message: error.message || 'Network error',
    status: 0,
  };
};
```

---

## Data Structures

**Note:** In JavaScript, we use JSDoc comments for documentation instead of TypeScript interfaces.

```javascript
/**
 * Login credentials
 * @typedef {Object} LoginCredentials
 * @property {string} [email] - User email
 * @property {string} [phone] - User phone
 * @property {string} password - User password
 * @property {string} [device_name] - Device name
 */

/**
 * Register data
 * @typedef {Object} RegisterData
 * @property {string} email - User email
 * @property {string} password - User password
 * @property {string} password_confirmation - Password confirmation
 * @property {string} [phone] - User phone
 * @property {string} [first] - First name
 * @property {string} [last] - Last name
 * @property {string} [company] - Company name
 * @property {string} [type] - User type
 * @property {string} [device_name] - Device name
 */

/**
 * Login response
 * @typedef {Object} LoginResponse
 * @property {boolean} success - Success flag
 * @property {string} message - Response message
 * @property {Object} data - Response data
 * @property {Object} data.user - User object
 * @property {Object} data.tokens - Token object
 */

/**
 * Token response
 * @typedef {Object} TokenResponse
 * @property {string} access_token - Access token (stored in memory)
 * @property {string} token_type - Token type
 * @property {number} expires_in - Expiration time in seconds
 * Note: refresh_token is NOT included in response - it's set as httpOnly cookie
 */
```

---

## Environment Variables

**Location:** `.env`

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=Owners Management System
```

---

## Usage Examples

### Login

```jsx
const LoginPage = () => {
  const { mutate: login, isLoading, error } = useLogin();
  const navigate = useNavigate();

  const handleSubmit = (data) => {
    login(data, {
      onSuccess: () => {
        navigate('/dashboard');
      },
    });
  };

  return <LoginForm onSubmit={handleSubmit} isLoading={isLoading} />;
};
```

### Get Current User

```jsx
const DashboardPage = () => {
  const { data: user, isLoading } = useCurrentUser();

  if (isLoading) return <Loading />;
  if (!user) return <Navigate to="/auth/login" />;

  return <div>Welcome, {user.name}</div>;
};
```

---

## Best Practices

1. **Centralized API Client** - Single axios instance
2. **Error Handling** - Consistent error handling
3. **Token Management** - Automatic refresh
4. **React Query** - Server state management
5. **Interceptors** - Request/response handling
6. **JSDoc Comments** - Document data structures

