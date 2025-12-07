# React Frontend Architecture

## Overview

This document defines the complete architecture for the React frontend application that will consume the Owners Management System API. The architecture is designed for scalability, maintainability, and follows modern React best practices.

---

## Technology Stack

### Core
- **React 19** - UI library
- **JavaScript (ES6+)** - Programming language
- **Vite** - Build tool and dev server

### State Management
- **Zustand** - Global state management
- **React Query (TanStack Query)** - Server state management

### Routing
- **React Router v6** - Client-side routing

### UI & Styling
- **Tailwind CSS** - Utility-first CSS
- **Shadcn/ui** or **Ant Design** - Component library
- **React Hook Form** - Form management

### HTTP Client
- **Axios** - HTTP client with interceptors

### Authentication
- **Custom hooks** - Auth state management
- **Token management** - Access & refresh token handling

### Permissions
- **Permission hooks** - Permission checking
- **Route guards** - Protected routes
- **Component guards** - Conditional rendering

---

## Project Structure

```
src/
├── api/                          # API layer
│   ├── client.js                 # Axios instance
│   ├── interceptors.js           # Request/Response interceptors
│   ├── endpoints.js              # API endpoints constants
│   └── v1/                       # API version 1
│       └── auth/
│           ├── auth.api.js       # Auth API calls
│           └── constants.js      # Auth constants
│
├── app/                          # App configuration
│   ├── router.jsx                # Router configuration
│   ├── store.js                  # Store configuration
│   └── providers.jsx             # App providers
│
├── assets/                       # Static assets
│   ├── images/
│   ├── icons/
│   └── fonts/
│
├── components/                   # Reusable components
│   ├── ui/                       # Base UI components
│   │   ├── Button/
│   │   ├── Input/
│   │   ├── Card/
│   │   └── ...
│   ├── layout/                   # Layout components
│   │   ├── Header/
│   │   ├── Sidebar/
│   │   ├── Footer/
│   │   └── Layout.tsx
│   ├── forms/                    # Form components
│   │   ├── FormInput/
│   │   ├── FormSelect/
│   │   └── ...
│   └── common/                   # Common components
│       ├── Loading/
│       ├── ErrorBoundary/
│       └── ...
│
├── features/                     # Feature modules
│   └── auth/                     # Auth feature module
│       ├── components/           # Auth-specific components
│       │   ├── LoginForm/
│       │   ├── RegisterForm/
│       │   ├── ForgotPasswordForm/
│       │   └── ResetPasswordForm/
│       ├── hooks/                # Auth hooks
│       │   ├── useAuth.js
│       │   ├── useLogin.js
│       │   ├── useRegister.js
│       │   └── usePermissions.js
│       ├── guards/               # Route guards
│       │   ├── ProtectedRoute.jsx
│       │   ├── PublicRoute.jsx
│       │   └── PermissionRoute.jsx
│       ├── services/             # Auth services
│       │   ├── auth.service.js
│       │   ├── token.service.js
│       │   └── permission.service.js
│       ├── store/                # Auth state
│       │   ├── auth.store.js
│       │   └── permission.store.js
│       └── utils/                # Auth utilities
│           ├── token.utils.js
│           └── permission.utils.js
│
├── hooks/                        # Global hooks
│   ├── useApi.js                 # API hook
│   ├── useDebounce.js
│   ├── useLocalStorage.js
│   └── ...
│
├── lib/                          # Utilities & helpers
│   ├── utils.js                  # General utilities
│   ├── constants.js              # App constants
│   ├── validations/              # Validation schemas
│   │   └── auth.schemas.js
│   └── helpers/                  # Helper functions
│       ├── format.js
│       └── ...
│
├── pages/                        # Page components
│   ├── auth/                     # Auth pages
│   │   ├── LoginPage.jsx
│   │   ├── RegisterPage.jsx
│   │   ├── ForgotPasswordPage.jsx
│   │   ├── ResetPasswordPage.jsx
│   │   └── VerifyEmailPage.jsx
│   ├── dashboard/                # Dashboard pages
│   └── ...
│
├── routes/                       # Route definitions
│   ├── index.jsx                 # Main routes
│   ├── auth.routes.jsx           # Auth routes
│   └── protected.routes.jsx      # Protected routes
│
├── styles/                       # Global styles
│   ├── globals.css
│   └── tailwind.css
│
├── App.jsx                       # Root component
├── main.jsx                      # Entry point
└── vite.config.js                # Vite configuration
```

---

## Auth Module Structure

### Detailed Auth Module

```
features/auth/
├── components/
│   ├── LoginForm/
│   │   ├── LoginForm.jsx
│   │   ├── LoginForm.test.js
│   │   └── index.js
│   ├── RegisterForm/
│   │   ├── RegisterForm.jsx
│   │   └── index.js
│   ├── ForgotPasswordForm/
│   │   ├── ForgotPasswordForm.jsx
│   │   └── index.js
│   ├── ResetPasswordForm/
│   │   ├── ResetPasswordForm.jsx
│   │   └── index.js
│   └── AuthLayout/
│       ├── AuthLayout.jsx
│       └── index.js
│
├── hooks/
│   ├── useAuth.js                # Main auth hook
│   ├── useLogin.js               # Login mutation
│   ├── useRegister.js            # Register mutation
│   ├── useLogout.js              # Logout mutation
│   ├── useRefreshToken.js        # Token refresh
│   ├── usePermissions.js         # Permission checking
│   ├── useHasPermission.js       # Single permission check
│   ├── useHasAnyPermission.js    # Any permission check
│   └── useHasAllPermissions.js   # All permissions check
│
├── guards/
│   ├── ProtectedRoute.jsx        # Requires authentication
│   ├── PublicRoute.jsx           # Public only (redirect if auth)
│   ├── PermissionRoute.jsx       # Requires specific permission
│   ├── RoleRoute.jsx             # Requires specific role
│   └── GuestRoute.jsx            # Guest only
│
├── services/
│   ├── auth.service.js           # Auth business logic
│   ├── token.service.js          # Token management
│   ├── permission.service.js     # Permission checking logic
│   └── storage.service.js        # Local storage management
│
├── store/
│   ├── auth.store.js             # Auth state (Zustand)
│   └── permission.store.js       # Permission state
│
├── utils/
│   ├── token.utils.js            # Token utilities
│   ├── permission.utils.js       # Permission utilities
│   ├── validation.utils.js       # Validation helpers
│   └── constants.js              # Auth constants
│
└── index.js                      # Public exports
```

---

## API Layer Structure

### API Client Setup

```
api/
├── client.js                     # Axios instance
├── interceptors.js               # Request/Response interceptors
├── endpoints.js                  # API endpoint constants
└── v1/
    └── auth/
        ├── auth.api.js           # Auth API functions
        └── queries.js            # React Query queries/mutations
```

---

## Permission System Architecture

### Permission Handling Strategy

1. **Permission Store** - Centralized permission state
2. **Permission Hooks** - Easy permission checking
3. **Route Guards** - Permission-based routing
4. **Component Guards** - Conditional rendering
5. **API Integration** - Fetch permissions from API

### Permission Flow

```
User Login
    ↓
Fetch User Data + Permissions
    ↓
Store in Auth Store
    ↓
Cache Permissions
    ↓
Use in Guards & Components
```

---

## State Management

### Auth State Structure

```javascript
// Auth state shape
{
  user: null, // User object or null
  tokens: {
    accessToken: null, // string or null
    refreshToken: null, // string or null
  },
  isAuthenticated: false, // boolean
  isLoading: false, // boolean
  permissions: [], // string[]
  roles: [], // string[]
}
```

### Permission State Structure

```javascript
// Permission state shape
{
  permissions: [], // string[]
  roles: [], // string[]
  isLoaded: false, // boolean
  hasPermission: (permission) => boolean,
  hasAnyPermission: (permissions) => boolean,
  hasAllPermissions: (permissions) => boolean,
  hasRole: (role) => boolean,
}
```

---

## Routing Architecture

### Route Structure

```
/                           # Public - Landing page
/auth                       # Public - Auth layout
  /auth/login              # Public - Login
  /auth/register           # Public - Register
  /auth/forgot-password    # Public - Forgot password
  /auth/reset-password     # Public - Reset password
  /auth/verify-email       # Public - Verify email
/dashboard                  # Protected - Dashboard
  /dashboard/overview      # Protected - Overview
  /dashboard/users         # Permission: auth.users.view
  /dashboard/roles         # Permission: auth.roles.view
  /dashboard/ownerships    # Permission: ownerships.view
  ...
```

---

## Component Architecture

### Component Hierarchy

```
App
├── Providers (Auth, Query, Router)
├── Router
│   ├── Public Routes
│   │   ├── Landing Page
│   │   └── Auth Routes
│   │       ├── Login
│   │       ├── Register
│   │       └── ...
│   └── Protected Routes
│       ├── Layout (with Sidebar, Header)
│       └── Feature Pages
│           ├── Dashboard
│           ├── Users (with permission check)
│           └── ...
└── Error Boundary
```

---

## File Examples

### Key File Structures

Each major file will be documented with:
- Purpose
- Dependencies
- Exports
- Usage examples

---

## Best Practices

1. **Component Organization** - Feature-based structure
2. **Code Splitting** - Lazy loading for routes
3. **Error Handling** - Comprehensive error boundaries
4. **Performance** - Memoization, code splitting
5. **Security** - Token management, XSS prevention
6. **Testing** - Unit and integration tests
7. **Code Quality** - ESLint, Prettier for consistent code style

---

## Next Steps

1. Set up project structure
2. Configure build tools
3. Set up API layer
4. Implement auth module
5. Implement permission system
6. Create UI components
7. Build pages and routes

