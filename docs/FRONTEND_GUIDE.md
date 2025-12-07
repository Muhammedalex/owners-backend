# دليل المطورين - Frontend Integration Guide

## نظرة عامة

هذا الدليل يشرح كيفية التعامل مع API الخاص بنظام إدارة الملكيات (Ownership Management System). النظام يعتمد على:
- **Authentication**: Laravel Sanctum (Access Token + Refresh Token)
- **Ownership Scoping**: Cookie-based ownership scope
- **User Types**: Super Admin و Owner

---

## 1. Authentication Flow (تدفق المصادقة)

### 1.1 Login (تسجيل الدخول)

**Endpoint:** `POST /api/v1/auth/login`

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "iPhone 14 Pro"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "user": {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "user@example.com",
            "name": "John Doe",
            "roles": ["owner"],
            "permissions": ["ownerships.view", "properties.view"]
        },
        "tokens": {
            "access_token": "1|xxxxxxxxxxxxx",
            "token_type": "Bearer",
            "expires_in": 3600
        }
    }
}
```

**⚠️ مهم جداً:**
- **Access Token**: يتم إرجاعه في JSON response - احفظه في memory (state) واستخدمه في `Authorization: Bearer {token}` header
- **Refresh Token**: يتم إرساله في httpOnly cookie تلقائياً - لا يمكنك الوصول إليه من JavaScript
- **Ownership UUID Cookie**: يتم ضبطه تلقائياً في cookie إذا كان للمستخدم default ownership

**Cookies التي يتم ضبطها تلقائياً:**
- `refresh_token` (httpOnly, secure, SameSite=strict)
- `ownership_uuid` (httpOnly, secure, SameSite=strict) - فقط إذا كان للمستخدم default ownership

---

### 1.2 Refresh Token (تجديد التوكن)

**Endpoint:** `POST /api/v1/auth/refresh`

**Headers:**
```
Authorization: Bearer {access_token}
```

**⚠️ مهم:**
- لا حاجة لإرسال refresh token في body - يتم قراءته من cookie تلقائياً
- Response يحتوي على access token جديد
- يتم تحديث `ownership_uuid` cookie تلقائياً إذا كان موجوداً

**Response:**
```json
{
    "success": true,
    "message": "Token refreshed successfully.",
    "data": {
        "user": {...},
        "tokens": {
            "access_token": "2|xxxxxxxxxxxxx",
            "token_type": "Bearer",
            "expires_in": 3600
        }
    }
}
```

---

### 1.3 Logout (تسجيل الخروج)

**Endpoint:** `POST /api/v1/auth/logout`

**Headers:**
```
Authorization: Bearer {access_token}
```

**⚠️ مهم:**
- يتم حذف refresh_token و ownership_uuid cookies تلقائياً
- احذف access token من state/memory

---

## 2. Ownership Scoping System (نظام تحديد نطاق الملكية)

### 2.1 كيف يعمل النظام؟

النظام يعتمد على **Cookie-based Ownership Scope**:

1. عند Login: يتم ضبط `ownership_uuid` cookie تلقائياً (إذا كان للمستخدم default ownership)
2. عند أي Request: الـ Backend يقرأ `ownership_uuid` من cookie ويحدد الـ ownership الحالي
3. جميع الـ API calls تلقائياً تكون scoped للـ ownership الحالي

### 2.2 Switch Ownership (تبديل الملكية)

**Endpoint:** `POST /api/v1/ownerships/{ownership_uuid}/switch`

**Headers:**
```
Authorization: Bearer {access_token}
```

**⚠️ مهم:**
- يجب أن يكون المستخدم لديه access للـ ownership
- يتم تحديث `ownership_uuid` cookie تلقائياً
- بعد Switch، جميع الـ API calls ستكون scoped للـ ownership الجديد

**Response:**
```json
{
    "success": true,
    "message": "Ownership switched successfully.",
    "data": {
        "ownership": {
            "uuid": "26f8eaab-9100-4bb0-ad85-c99af54eb7fe",
            "name": "Ownership Name",
            "city": "Riyadh",
            "active": true
        }
    }
}
```

### 2.3 Get User Ownerships (الحصول على ملكيات المستخدم)

**Endpoint:** `GET /api/v1/users/{user_uuid}/ownerships`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "default": true,
            "ownership": {
                "uuid": "26f8eaab-9100-4bb0-ad85-c99af54eb7fe",
                "name": "Ownership 1",
                "type": "company",
                "ownership_type": "residential"
            },
            "created_at": "2025-01-01T00:00:00.000000Z"
        },
        {
            "id": 2,
            "default": false,
            "ownership": {
                "uuid": "3d2eeeb1-94ae-462d-a368-cf8ecba72523",
                "name": "Ownership 2",
                "type": "company",
                "ownership_type": "commercial"
            },
            "created_at": "2025-01-02T00:00:00.000000Z"
        }
    ]
}
```

---

## 3. User Creation (إنشاء المستخدمين)

### 3.1 Super Admin vs Owner

#### Super Admin:
- يمكنه إنشاء مستخدمين **بدون ربط** بأي ownership
- يمكنه ربط المستخدمين لاحقاً باستخدام `POST /api/v1/ownerships/users/assign`

#### Owner (غير Super Admin):
- عند إنشاء مستخدم، يتم **ربطه تلقائياً** بـ ownership الحالي (من cookie)
- لا يمكنه استخدام endpoint `assign` - يجب إنشاء المستخدم مباشرة

### 3.2 Create User

**Endpoint:** `POST /api/v1/users`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
    "email": "newuser@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "phone": "+966501234567",
    "first": "John",
    "last": "Doe",
    "company": "Example Company",
    "type": "owner",
    "active": true,
    "timezone": "Asia/Riyadh",
    "locale": "ar",
    "roles": [1, 2],
    "is_default": false
}
```

**⚠️ مهم:**
- `is_default`: لتحديد ownership كافتراضي للمستخدم (فقط لـ non-Super Admin)
- إذا كان المستخدم الحالي **Owner** (ليس Super Admin):
  - المستخدم الجديد سيتم ربطه تلقائياً بـ ownership من cookie
  - إذا `is_default = true`: سيصبح هذا ownership هو الافتراضي للمستخدم الجديد

**Response:**
```json
{
    "success": true,
    "message": "User created successfully. User automatically linked to current ownership.",
    "data": {
        "id": 5,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "email": "newuser@example.com",
        "name": "John Doe",
        "roles": ["owner"]
    }
}
```

---

## 4. Ownership Management (إدارة الملكيات)

### 4.1 List Ownerships

**Endpoint:** `GET /api/v1/ownerships`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
- `per_page`: عدد العناصر في الصفحة (default: 15)
- `search`: البحث في name, legal, registration, tax_id
- `type`: تصفية حسب النوع
- `ownership_type`: تصفية حسب فئة الملكية
- `city`: تصفية حسب المدينة
- `active`: تصفية حسب الحالة (true/false)

**⚠️ مهم:**
- Super Admin: يرى جميع الملكيات
- Owner: يرى فقط الملكيات التي لديه access لها

---

### 4.2 Board Members (أعضاء مجلس الإدارة)

**⚠️ مهم جداً:**
- جميع endpoints للـ Board Members **لا تحتاج** `ownership_uuid` في URL
- الـ ownership يتم تحديده تلقائياً من cookie

#### List Board Members
**Endpoint:** `GET /api/v1/ownerships/board-members`

#### Add Board Member
**Endpoint:** `POST /api/v1/ownerships/board-members`

**Request Body:**
```json
{
    "user_id": 1,
    "role": "Chairman",
    "active": true,
    "start_date": "2025-01-01",
    "end_date": null
}
```

#### Update Board Member
**Endpoint:** `PUT /api/v1/ownerships/board-members/{id}`

#### Remove Board Member
**Endpoint:** `DELETE /api/v1/ownerships/board-members/{id}`

---

### 4.3 User-Ownership Mapping

**⚠️ مهم جداً:**
- جميع endpoints للـ User Mapping **لا تحتاج** `ownership_uuid` في URL
- الـ ownership يتم تحديده تلقائياً من cookie

#### Get Ownership Users
**Endpoint:** `GET /api/v1/ownerships/users`

#### Assign User to Ownership
**Endpoint:** `POST /api/v1/ownerships/users/assign`

**⚠️ مهم:**
- **Super Admin Only** - Regular owners سيحصلون على 403 error
- Regular owners يجب أن يستخدموا Create User مباشرة (الربط تلقائي)

**Request Body:**
```json
{
    "user_id": 1,
    "default": false
}
```

#### Remove User from Ownership
**Endpoint:** `DELETE /api/v1/ownerships/users/{user_uuid}`

---

## 5. Important Notes (ملاحظات مهمة)

### 5.1 Cookies Management

**⚠️ مهم جداً:**
- جميع cookies هي **httpOnly** - لا يمكن الوصول إليها من JavaScript
- Cookies يتم إرسالها تلقائياً مع كل request
- لا تحتاج لإدارة cookies يدوياً - الـ browser يقوم بذلك تلقائياً

**Cookies المستخدمة:**
- `refresh_token`: لتجديد access token
- `ownership_uuid`: لتحديد الـ ownership الحالي

### 5.2 Access Token Management

**⚠️ مهم:**
- Access Token يتم إرجاعه في JSON response فقط
- احفظه في state/memory (مثل Redux, Context, Vuex, etc.)
- استخدمه في `Authorization: Bearer {token}` header لكل request
- Access Token expires بعد 1 ساعة (3600 ثانية)
- عند expiration، استخدم Refresh Token endpoint

### 5.3 Error Handling

**401 Unauthorized:**
- Access token expired أو غير صحيح
- استخدم Refresh Token endpoint

**403 Forbidden:**
- المستخدم ليس لديه permission
- أو لا يمكنه الوصول للـ ownership المطلوب

**404 Not Found:**
- Resource غير موجود
- أو المستخدم ليس لديه access للـ resource

### 5.4 Ownership Scope

**⚠️ مهم جداً:**
- جميع الـ API calls (عدا Ownership CRUD) تلقائياً تكون scoped للـ ownership الحالي
- لا تحتاج لإرسال `ownership_id` أو `ownership_uuid` في parameters
- الـ Backend يحدد الـ ownership من cookie تلقائياً

**Endpoints التي تحتاج `ownership_uuid` في URL:**
- `GET /api/v1/ownerships/{uuid}` - Get specific ownership
- `PUT /api/v1/ownerships/{uuid}` - Update ownership
- `DELETE /api/v1/ownerships/{uuid}` - Delete ownership
- `POST /api/v1/ownerships/{uuid}/activate` - Activate ownership
- `POST /api/v1/ownerships/{uuid}/deactivate` - Deactivate ownership
- `POST /api/v1/ownerships/{uuid}/switch` - Switch ownership

**Endpoints التي لا تحتاج `ownership_uuid` (يتم تحديده من cookie):**
- `GET /api/v1/ownerships/board-members` - List board members
- `POST /api/v1/ownerships/board-members` - Add board member
- `GET /api/v1/ownerships/users` - Get ownership users
- `POST /api/v1/ownerships/users/assign` - Assign user (Super Admin only)

---

## 6. Frontend Implementation Checklist

### ✅ Authentication
- [ ] حفظ Access Token في state/memory
- [ ] إضافة `Authorization: Bearer {token}` header لكل request
- [ ] Handle 401 errors وتجديد token تلقائياً
- [ ] Clear access token عند logout

### ✅ Ownership Management
- [ ] عرض قائمة ownerships للمستخدم
- [ ] Switch ownership functionality
- [ ] عرض الـ ownership الحالي في UI
- [ ] Handle ownership scope changes

### ✅ User Management
- [ ] Create user form (مع handle للـ Super Admin vs Owner)
- [ ] عرض رسالة عند ربط المستخدم تلقائياً
- [ ] Handle permissions (Super Admin vs Owner)

### ✅ Board Members
- [ ] List board members (لا حاجة لـ ownership_uuid في URL)
- [ ] Add/Update/Delete board members
- [ ] عرض board members للـ ownership الحالي

### ✅ Error Handling
- [ ] Handle 401 (token expired)
- [ ] Handle 403 (permission denied)
- [ ] Handle 404 (not found)
- [ ] عرض رسائل خطأ واضحة للمستخدم

### ✅ Cookies
- [ ] لا تحاول الوصول للـ cookies من JavaScript
- [ ] تأكد من أن cookies يتم إرسالها تلقائياً (credentials: 'include' في fetch)
- [ ] Handle cookie expiration

---

## 7. Example API Client (مثال)

```javascript
// apiClient.js
class ApiClient {
    constructor() {
        this.baseURL = process.env.REACT_APP_API_URL || 'http://localhost:8000';
        this.accessToken = null;
    }

    setAccessToken(token) {
        this.accessToken = token;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(this.accessToken && { 'Authorization': `Bearer ${this.accessToken}` }),
                ...options.headers,
            },
            credentials: 'include', // Important: Send cookies automatically
        };

        const response = await fetch(url, config);
        
        if (response.status === 401) {
            // Token expired, try to refresh
            const refreshed = await this.refreshToken();
            if (refreshed) {
                // Retry the request
                config.headers['Authorization'] = `Bearer ${this.accessToken}`;
                return fetch(url, config);
            }
            throw new Error('Unauthorized');
        }

        return response.json();
    }

    async login(email, password, deviceName) {
        const response = await this.request('/api/v1/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password, device_name: deviceName }),
        });

        if (response.success) {
            this.setAccessToken(response.data.tokens.access_token);
        }

        return response;
    }

    async refreshToken() {
        const response = await this.request('/api/v1/auth/refresh', {
            method: 'POST',
        });

        if (response.success) {
            this.setAccessToken(response.data.tokens.access_token);
            return true;
        }

        return false;
    }

    async logout() {
        const response = await this.request('/api/v1/auth/logout', {
            method: 'POST',
        });

        this.setAccessToken(null);
        return response;
    }

    async switchOwnership(ownershipUuid) {
        return this.request(`/api/v1/ownerships/${ownershipUuid}/switch`, {
            method: 'POST',
        });
    }

    async getUserOwnerships(userUuid) {
        return this.request(`/api/v1/users/${userUuid}/ownerships`);
    }

    async getOwnerships(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`/api/v1/ownerships?${queryString}`);
    }

    async getBoardMembers() {
        return this.request('/api/v1/ownerships/board-members');
    }

    async createUser(userData) {
        return this.request('/api/v1/users', {
            method: 'POST',
            body: JSON.stringify(userData),
        });
    }
}

export default new ApiClient();
```

---

## 8. Summary (ملخص)

### المفاتيح الأساسية:
1. **Access Token**: في header فقط - احفظه في state
2. **Refresh Token**: في httpOnly cookie - لا يمكن الوصول إليه
3. **Ownership UUID**: في httpOnly cookie - يتم تحديده تلقائياً
4. **Ownership Scope**: جميع الـ API calls تلقائياً scoped للـ ownership الحالي
5. **User Creation**: Super Admin vs Owner لهما سلوك مختلف

### أهم النقاط:
- ✅ استخدم `credentials: 'include'` في fetch لضمان إرسال cookies
- ✅ Handle 401 errors وتجديد token تلقائياً
- ✅ لا تحاول الوصول للـ cookies من JavaScript
- ✅ معظم endpoints لا تحتاج `ownership_uuid` في URL
- ✅ Super Admin يمكنه إنشاء users بدون ربط، Owner يربط تلقائياً

---

**آخر تحديث:** 2025-01-04

