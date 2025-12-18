# Tenant Module API Documentation

## Overview

Complete API documentation for the Tenant Management Module. This module handles tenant profiles, identity documents, commercial registration, municipality licenses, and all related information.

**Base URL:** `/api/v1/tenants`

**Authentication:** All endpoints require Bearer token authentication and ownership scope.

---

## Table of Contents

1. [Authentication](#authentication)
2. [Endpoints](#endpoints)
   - [List Tenants](#list-tenants)
   - [Get Tenant](#get-tenant)
   - [Create Tenant](#create-tenant)
   - [Update Tenant](#update-tenant)
   - [Delete Tenant](#delete-tenant)
3. [Data Models](#data-models)
4. [Error Handling](#error-handling)
5. [Examples](#examples)

---

## Authentication

### Login

**Endpoint:** `POST /api/v1/auth/login`

**Description:** Login to get access token. Required before accessing tenant endpoints.

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "password123",
  "device_name": "Postman"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "name": "Admin User"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
    }
  }
}
```

**Important Notes:**
- Access token is returned in JSON response (save to environment variable)
- Refresh token is stored in httpOnly cookie (automatically managed)
- Ownership UUID is stored in httpOnly cookie (automatically managed)
- Use access token in Authorization header: `Bearer {access_token}`

---

## Endpoints

### List Tenants

**Endpoint:** `GET /api/v1/tenants`

**Description:** Get paginated list of tenants for the current ownership.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `per_page` | integer | No | Items per page (default: 15, use -1 for all) |
| `search` | string | No | Search term (searches in name, email, national_id) |
| `rating` | string | No | Filter by rating: `excellent`, `good`, `fair`, `poor` |
| `employment` | string | No | Filter by employment: `employed`, `self_employed`, `unemployed`, `retired`, `student` |

**Example Request:**
```
GET /api/v1/tenants?per_page=15&search=ahmed&rating=good&employment=employed
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "تم جلب البيانات بنجاح",
  "data": [
    {
      "id": 1,
      "user": {
        "id": 5,
        "email": "ahmed@example.com",
        "name": "أحمد علي"
      },
      "ownership": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "شركة الراشد العقارية"
      },
      "national_id": "1234567890",
      "id_type": "national_id",
      "id_expiry": "2030-12-31",
      "id_valid": true,
      "id_expired": false,
      "id_document_image": {
        "id": 1,
        "type": "tenant_id_document",
        "url": "http://localhost:8000/storage/media/tenant/1/tenant_id_document/image.jpg",
        "name": "id_document.jpg"
      },
      "commercial_registration_number": "CR-1234567890",
      "commercial_registration_expiry": "2030-12-31",
      "commercial_owner_name": "أحمد محمد",
      "commercial_registration_image": {
        "id": 2,
        "type": "tenant_cr_document",
        "url": "http://localhost:8000/storage/media/tenant/1/tenant_cr_document/cr.jpg",
        "name": "cr_document.jpg"
      },
      "municipality_license_number": "MUN-123456",
      "municipality_license_image": {
        "id": 3,
        "type": "tenant_municipality_license",
        "url": "http://localhost:8000/storage/media/tenant/1/tenant_municipality_license/license.jpg",
        "name": "municipality_license.jpg"
      },
      "emergency_name": "محمد علي",
      "emergency_phone": "+966507654321",
      "emergency_relation": "أخ",
      "employment": "employed",
      "employer": "شركة التقنية المتقدمة",
      "income": 15000.00,
      "rating": "good",
      "notes": "مستأجر موثوق",
      "contracts_count": 2,
      "created_at": "2025-01-15T10:00:00+00:00",
      "updated_at": "2025-12-10T14:20:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### Get Tenant

**Endpoint:** `GET /api/v1/tenants/{tenant}`

**Description:** Get single tenant details by ID.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tenant` | integer | Yes | Tenant ID |

**Example Request:**
```
GET /api/v1/tenants/1
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "تم جلب البيانات بنجاح",
  "data": {
    "id": 1,
    "user": {
      "id": 5,
      "uuid": "550e8400-e29b-41d4-a716-446655440005",
      "email": "ahmed@example.com",
      "phone": "+966501234567",
      "name": "أحمد علي محمد",
      "first": "أحمد",
      "last": "محمد"
    },
    "ownership": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "شركة الراشد العقارية",
      "legal": "شركة الراشد العقارية",
      "type": "company"
    },
    "national_id": "1234567890",
    "id_type": "national_id",
    "id_document": "documents/tenants/1234567890.pdf",
    "id_expiry": "2030-12-31",
    "id_valid": true,
    "id_expired": false,
    "id_document_image": {
      "id": 1,
      "type": "tenant_id_document",
      "url": "http://localhost:8000/storage/media/tenant/1/tenant_id_document/id_20251218_abc123.jpg",
      "name": "id_document.jpg",
      "size": 245678,
      "human_readable_size": "240.00 KB",
      "mime": "image/jpeg",
      "is_image": true,
      "created_at": "2025-12-18T10:00:00+00:00"
    },
    "commercial_registration_number": "CR-1234567890",
    "commercial_registration_expiry": "2030-12-31",
    "commercial_owner_name": "أحمد محمد",
    "commercial_registration_image": {
      "id": 2,
      "type": "tenant_cr_document",
      "url": "http://localhost:8000/storage/media/tenant/1/tenant_cr_document/cr_20251218_def456.jpg",
      "name": "cr_document.jpg",
      "size": 312456,
      "human_readable_size": "305.13 KB",
      "mime": "image/jpeg",
      "is_image": true,
      "created_at": "2025-12-18T10:05:00+00:00"
    },
    "municipality_license_number": "MUN-123456",
    "municipality_license_image": {
      "id": 3,
      "type": "tenant_municipality_license",
      "url": "http://localhost:8000/storage/media/tenant/1/tenant_municipality_license/license_20251218_ghi789.jpg",
      "name": "municipality_license.jpg",
      "size": 198765,
      "human_readable_size": "194.11 KB",
      "mime": "image/jpeg",
      "is_image": true,
      "created_at": "2025-12-18T10:10:00+00:00"
    },
    "emergency_name": "محمد علي",
    "emergency_phone": "+966507654321",
    "emergency_relation": "أخ",
    "employment": "employed",
    "employer": "شركة التقنية المتقدمة",
    "income": 15000.00,
    "rating": "good",
    "notes": "مستأجر موثوق، يدفع في الوقت المحدد",
    "contracts": [
      {
        "id": 1,
        "uuid": "660e8400-e29b-41d4-a716-446655440001",
        "number": "CNT-001-2025-00001",
        "status": "active",
        "rent": 6000.00,
        "start": "2025-01-01",
        "end": "2025-12-31"
      }
    ],
    "contracts_count": 1,
    "created_at": "2025-01-15T10:00:00+00:00",
    "updated_at": "2025-12-18T14:20:00+00:00"
  }
}
```

---

### Create Tenant

**Endpoint:** `POST /api/v1/tenants`

**Description:** Create a new tenant with optional file uploads.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_id` | integer | **Yes** | User ID (must exist and be unique) |
| `national_id` | string | No | National ID number (max 50 chars) |
| `id_type` | string | No | ID type: `national_id`, `iqama`, `passport`, `commercial_registration` |
| `id_expiry` | date | No | ID expiry date (YYYY-MM-DD) |
| `id_document_image` | file | No | ID document image (jpg, jpeg, png, gif) |
| `commercial_registration_number` | string | No | Commercial registration number (max 100 chars) |
| `commercial_registration_expiry` | date | No | Commercial registration expiry (YYYY-MM-DD) |
| `commercial_owner_name` | string | No | Commercial registration owner name (max 255 chars) |
| `commercial_registration_image` | file | No | Commercial registration document image |
| `municipality_license_number` | string | No | Municipality license number (max 100 chars) |
| `municipality_license_image` | file | No | Municipality license image |
| `emergency_name` | string | No | Emergency contact name (max 100 chars) |
| `emergency_phone` | string | No | Emergency contact phone (Saudi format: +966XXXXXXXXX) |
| `emergency_relation` | string | No | Emergency contact relation (max 50 chars) |
| `employment` | string | No | Employment status: `employed`, `self_employed`, `unemployed`, `retired`, `student` |
| `employer` | string | No | Employer name (max 255 chars) |
| `income` | decimal | No | Monthly income (max 9999999999.99) |
| `rating` | string | No | Rating: `excellent`, `good`, `fair`, `poor` |
| `notes` | text | No | Notes about tenant |

**Example Request (cURL):**
```bash
curl -X POST "http://localhost:8000/api/v1/tenants" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -F "user_id=5" \
  -F "national_id=1234567890" \
  -F "id_type=national_id" \
  -F "id_expiry=2030-12-31" \
  -F "id_document_image=@/path/to/id_document.jpg" \
  -F "commercial_registration_number=CR-1234567890" \
  -F "commercial_registration_expiry=2030-12-31" \
  -F "commercial_owner_name=أحمد محمد" \
  -F "commercial_registration_image=@/path/to/cr_document.jpg" \
  -F "municipality_license_number=MUN-123456" \
  -F "municipality_license_image=@/path/to/license.jpg" \
  -F "emergency_name=محمد علي" \
  -F "emergency_phone=+966507654321" \
  -F "emergency_relation=أخ" \
  -F "employment=employed" \
  -F "employer=شركة التقنية المتقدمة" \
  -F "income=15000.00" \
  -F "rating=good" \
  -F "notes=مستأجر موثوق"
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "تم الإنشاء بنجاح",
  "data": {
    "id": 1,
    "user": { ... },
    "ownership": { ... },
    "national_id": "1234567890",
    "id_type": "national_id",
    "id_expiry": "2030-12-31",
    "id_document_image": {
      "id": 1,
      "type": "tenant_id_document",
      "url": "http://localhost:8000/storage/media/tenant/1/tenant_id_document/id_20251218_abc123.jpg",
      "name": "id_document.jpg"
    },
    "commercial_registration_number": "CR-1234567890",
    "commercial_registration_expiry": "2030-12-31",
    "commercial_owner_name": "أحمد محمد",
    "commercial_registration_image": {
      "id": 2,
      "type": "tenant_cr_document",
      "url": "http://localhost:8000/storage/media/tenant/1/tenant_cr_document/cr_20251218_def456.jpg",
      "name": "cr_document.jpg"
    },
    "municipality_license_number": "MUN-123456",
    "municipality_license_image": {
      "id": 3,
      "type": "tenant_municipality_license",
      "url": "http://localhost:8000/storage/media/tenant/1/tenant_municipality_license/license_20251218_ghi789.jpg",
      "name": "municipality_license.jpg"
    },
    "emergency_name": "محمد علي",
    "emergency_phone": "+966507654321",
    "emergency_relation": "أخ",
    "employment": "employed",
    "employer": "شركة التقنية المتقدمة",
    "income": 15000.00,
    "rating": "good",
    "notes": "مستأجر موثوق",
    "created_at": "2025-12-18T10:00:00+00:00",
    "updated_at": "2025-12-18T10:00:00+00:00"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "فشل التحقق من البيانات",
  "errors": {
    "user_id": ["The user id field is required."],
    "user_id.exists": ["The selected user id is invalid."],
    "user_id.unique": ["The user id has already been taken."]
  }
}
```

---

### Update Tenant

**Endpoint:** `PUT /api/v1/tenants/{tenant}` or `PATCH /api/v1/tenants/{tenant}`

**Description:** Update an existing tenant. All fields are optional. Only provided fields will be updated.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: multipart/form-data
```

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tenant` | integer | Yes | Tenant ID |

**Request Body:** Same as Create Tenant, but all fields are optional.

**Important Notes:**
- Only include fields you want to update
- Uploading a new image replaces the existing one
- Ownership ID cannot be changed
- User ID can be changed (must be unique)

**Example Request:**
```bash
curl -X PUT "http://localhost:8000/api/v1/tenants/1" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -F "national_id=9876543210" \
  -F "id_expiry=2035-12-31" \
  -F "id_document_image=@/path/to/new_id_document.jpg" \
  -F "rating=excellent"
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "تم التحديث بنجاح",
  "data": {
    "id": 1,
    "national_id": "9876543210",
    "id_expiry": "2035-12-31",
    "id_document_image": {
      "id": 4,
      "type": "tenant_id_document",
      "url": "http://localhost:8000/storage/media/tenant/1/tenant_id_document/id_20251218_new123.jpg",
      "name": "new_id_document.jpg"
    },
    "rating": "excellent",
    "updated_at": "2025-12-18T15:30:00+00:00"
  }
}
```

---

### Delete Tenant

**Endpoint:** `DELETE /api/v1/tenants/{tenant}`

**Description:** Delete a tenant by ID.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tenant` | integer | Yes | Tenant ID |

**Example Request:**
```
DELETE /api/v1/tenants/1
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "تم الحذف بنجاح",
  "data": null
}
```

**Note:** This will also delete associated media files. Make sure tenant has no active contracts before deleting.

---

## Data Models

### Tenant Object

```typescript
interface Tenant {
  id: number;
  user: User | null;
  ownership: Ownership;
  national_id: string | null;
  id_type: 'national_id' | 'iqama' | 'passport' | 'commercial_registration' | null;
  id_document: string | null; // Legacy field
  id_expiry: string | null; // YYYY-MM-DD
  id_valid: boolean;
  id_expired: boolean;
  id_document_image: MediaFile | null;
  commercial_registration_number: string | null;
  commercial_registration_expiry: string | null; // YYYY-MM-DD
  commercial_owner_name: string | null;
  commercial_registration_image: MediaFile | null;
  municipality_license_number: string | null;
  municipality_license_image: MediaFile | null;
  emergency_name: string | null;
  emergency_phone: string | null;
  emergency_relation: string | null;
  employment: 'employed' | 'self_employed' | 'unemployed' | 'retired' | 'student' | null;
  employer: string | null;
  income: number | null;
  rating: 'excellent' | 'good' | 'fair' | 'poor' | null;
  notes: string | null;
  contracts: Contract[];
  contracts_count: number | null;
  created_at: string; // ISO 8601
  updated_at: string; // ISO 8601
}
```

### MediaFile Object

```typescript
interface MediaFile {
  id: number;
  type: string;
  url: string;
  name: string;
  size: number;
  human_readable_size: string;
  mime: string;
  title: string | null;
  description: string | null;
  order: number;
  public: boolean;
  is_image: boolean;
  is_video: boolean;
  uploaded_by: {
    id: number;
    name: string;
  } | null;
  created_at: string; // ISO 8601
  updated_at: string; // ISO 8601
}
```

---

## Error Handling

### Standard Error Response

```json
{
  "success": false,
  "message": "Error message key",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

### Common Error Codes

| Status Code | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request (validation errors) |
| 401 | Unauthorized (invalid or missing token) |
| 403 | Forbidden (no permission) |
| 404 | Not Found |
| 422 | Unprocessable Entity |
| 500 | Internal Server Error |

### Common Error Messages

- `messages.errors.ownership_required` - Ownership scope is required
- `messages.errors.not_found` - Resource not found
- `messages.errors.unauthorized` - Invalid or missing authentication token
- `messages.errors.forbidden` - No permission to access resource
- `messages.validation.required` - Field is required
- `messages.validation.exists` - Field value doesn't exist
- `messages.validation.unique` - Field value already exists

---

## Examples

### Frontend Integration Example (JavaScript/React)

```javascript
// Login
async function login(email, password) {
  const response = await fetch('http://localhost:8000/api/v1/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include', // Important: Include cookies
    body: JSON.stringify({
      email,
      password,
      device_name: 'Web Browser'
    })
  });
  
  const data = await response.json();
  if (data.success) {
    localStorage.setItem('access_token', data.data.tokens.access_token);
    return data;
  }
  throw new Error(data.message);
}

// Create Tenant with File Upload
async function createTenant(tenantData, files) {
  const formData = new FormData();
  
  // Add text fields
  formData.append('user_id', tenantData.user_id);
  formData.append('national_id', tenantData.national_id || '');
  formData.append('id_type', tenantData.id_type || '');
  formData.append('id_expiry', tenantData.id_expiry || '');
  
  // Add files
  if (files.id_document_image) {
    formData.append('id_document_image', files.id_document_image);
  }
  if (files.commercial_registration_image) {
    formData.append('commercial_registration_image', files.commercial_registration_image);
  }
  if (files.municipality_license_image) {
    formData.append('municipality_license_image', files.municipality_license_image);
  }
  
  const response = await fetch('http://localhost:8000/api/v1/tenants', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
      'Accept': 'application/json',
      // Don't set Content-Type - browser will set it with boundary
    },
    credentials: 'include', // Important: Include cookies for ownership scope
    body: formData
  });
  
  return await response.json();
}

// Get Tenant List
async function getTenants(filters = {}) {
  const params = new URLSearchParams({
    per_page: filters.per_page || 15,
    ...(filters.search && { search: filters.search }),
    ...(filters.rating && { rating: filters.rating }),
    ...(filters.employment && { employment: filters.employment })
  });
  
  const response = await fetch(`http://localhost:8000/api/v1/tenants?${params}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
      'Accept': 'application/json',
    },
    credentials: 'include'
  });
  
  return await response.json();
}

// Update Tenant
async function updateTenant(tenantId, updates, files = {}) {
  const formData = new FormData();
  
  // Add only fields that are being updated
  Object.keys(updates).forEach(key => {
    if (updates[key] !== null && updates[key] !== undefined) {
      formData.append(key, updates[key]);
    }
  });
  
  // Add files if provided
  Object.keys(files).forEach(key => {
    if (files[key]) {
      formData.append(key, files[key]);
    }
  });
  
  const response = await fetch(`http://localhost:8000/api/v1/tenants/${tenantId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
      'Accept': 'application/json',
    },
    credentials: 'include',
    body: formData
  });
  
  return await response.json();
}

// Delete Tenant
async function deleteTenant(tenantId) {
  const response = await fetch(`http://localhost:8000/api/v1/tenants/${tenantId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
      'Accept': 'application/json',
    },
    credentials: 'include'
  });
  
  return await response.json();
}
```

---

## Postman Collection

A complete Postman collection is available at:
`docs/postman/Tenant_Module_API.postman_collection.json`

**To use:**
1. Import the collection into Postman
2. Set up environment variables:
   - `base_url`: Your API base URL (e.g., `http://localhost:8000`)
   - `access_token`: Will be automatically set after login
3. Run the Login request first
4. All other requests will use the access token automatically

---

## Notes for Frontend Developers

1. **Authentication:**
   - Always include `credentials: 'include'` in fetch requests to handle cookies
   - Store access token in localStorage or state
   - Refresh token is automatically managed via httpOnly cookies

2. **File Uploads:**
   - Use `FormData` for create/update requests with files
   - Don't set `Content-Type` header manually - browser will set it with boundary
   - Supported image formats: jpg, jpeg, png, gif
   - Files are automatically optimized and resized

3. **Ownership Scope:**
   - Ownership ID is automatically taken from cookie
   - All tenant operations are scoped to current ownership
   - No need to send ownership_id in requests

4. **Error Handling:**
   - Always check `success` field in response
   - Display validation errors from `errors` object
   - Handle 401 errors by redirecting to login

5. **Pagination:**
   - Use `per_page=-1` to get all tenants
   - Pagination metadata is in `meta` object
   - Default page size is 15

---

## Support

For questions or issues, please contact the backend development team.

**Last Updated:** December 18, 2025

