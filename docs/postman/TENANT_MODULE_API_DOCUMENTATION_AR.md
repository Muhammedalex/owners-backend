# توثيق API لوحدة المستأجرين

## نظرة عامة

توثيق API كامل لوحدة إدارة المستأجرين. هذه الوحدة تتعامل مع ملفات المستأجرين، وثائق الهوية، السجل التجاري، رخصة البلدية، وجميع المعلومات ذات الصلة.

**الرابط الأساسي:** `/api/v1/tenants`

**المصادقة:** جميع الـ endpoints تتطلب Bearer token authentication ونطاق الملكية (ownership scope).

---

## جدول المحتويات

1. [المصادقة](#المصادقة)
2. [الـ Endpoints](#الـ-endpoints)
   - [قائمة المستأجرين](#قائمة-المستأجرين)
   - [الحصول على مستأجر](#الحصول-على-مستأجر)
   - [إنشاء مستأجر](#إنشاء-مستأجر)
   - [تحديث مستأجر](#تحديث-مستأجر)
   - [حذف مستأجر](#حذف-مستأجر)
3. [نماذج البيانات](#نماذج-البيانات)
4. [معالجة الأخطاء](#معالجة-الأخطاء)
5. [أمثلة](#أمثلة)

---

## المصادقة

### تسجيل الدخول

**Endpoint:** `POST /api/v1/auth/login`

**الوصف:** تسجيل الدخول للحصول على access token. مطلوب قبل الوصول إلى endpoints المستأجرين.

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

**ملاحظات مهمة:**
- Access token يتم إرجاعه في JSON response (احفظه في environment variable)
- Refresh token يتم تخزينه في httpOnly cookie (يتم إدارته تلقائياً)
- Ownership UUID يتم تخزينه في httpOnly cookie (يتم إدارته تلقائياً)
- استخدم access token في Authorization header: `Bearer {access_token}`

---

## الـ Endpoints

### قائمة المستأجرين

**Endpoint:** `GET /api/v1/tenants`

**الوصف:** الحصول على قائمة المستأجرين مع التصفح (pagination) للملكية الحالية.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Query Parameters:**

| المعامل | النوع | مطلوب | الوصف |
|---------|------|-------|--------|
| `per_page` | integer | لا | عدد العناصر في الصفحة (افتراضي: 15، استخدم -1 للحصول على الكل) |
| `search` | string | لا | مصطلح البحث (يبحث في الاسم، البريد الإلكتروني، رقم الهوية) |
| `rating` | string | لا | التصفية حسب التقييم: `excellent`, `good`, `fair`, `poor` |
| `employment` | string | لا | التصفية حسب التوظيف: `employed`, `self_employed`, `unemployed`, `retired`, `student` |

**مثال Request:**
```
GET /api/v1/tenants?per_page=15&search=أحمد&rating=good&employment=employed
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

### الحصول على مستأجر

**Endpoint:** `GET /api/v1/tenants/{tenant}`

**الوصف:** الحصول على تفاصيل مستأجر واحد حسب ID.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Path Parameters:**

| المعامل | النوع | مطلوب | الوصف |
|---------|------|-------|--------|
| `tenant` | integer | نعم | معرف المستأجر |

**مثال Request:**
```
GET /api/v1/tenants/1
```

**Response (200 OK):** نفس Response في Create Tenant لكن مع تفاصيل كاملة.

---

### إنشاء مستأجر

**Endpoint:** `POST /api/v1/tenants`

**الوصف:** إنشاء مستأجر جديد مع رفع الملفات الاختياري.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**

| الحقل | النوع | مطلوب | الوصف |
|-------|------|-------|--------|
| `user_id` | integer | **نعم** | معرف المستخدم (يجب أن يكون موجوداً وفريداً) |
| `national_id` | string | لا | رقم الهوية الوطنية (حد أقصى 50 حرف) |
| `id_type` | string | لا | نوع الهوية: `national_id`, `iqama`, `passport`, `commercial_registration` |
| `id_expiry` | date | لا | تاريخ انتهاء الهوية (YYYY-MM-DD) |
| `id_document_image` | file | لا | صورة وثيقة الهوية (jpg, jpeg, png, gif) |
| `commercial_registration_number` | string | لا | رقم السجل التجاري (حد أقصى 100 حرف) |
| `commercial_registration_expiry` | date | لا | تاريخ انتهاء السجل التجاري (YYYY-MM-DD) |
| `commercial_owner_name` | string | لا | اسم مالك السجل التجاري (حد أقصى 255 حرف) |
| `commercial_registration_image` | file | لا | صورة وثيقة السجل التجاري |
| `municipality_license_number` | string | لا | رقم رخصة البلدية (حد أقصى 100 حرف) |
| `municipality_license_image` | file | لا | صورة رخصة البلدية |
| `emergency_name` | string | لا | اسم جهة الاتصال للطوارئ (حد أقصى 100 حرف) |
| `emergency_phone` | string | لا | هاتف جهة الاتصال للطوارئ (صيغة سعودية: +966XXXXXXXXX) |
| `emergency_relation` | string | لا | صلة جهة الاتصال للطوارئ (حد أقصى 50 حرف) |
| `employment` | string | لا | حالة التوظيف: `employed`, `self_employed`, `unemployed`, `retired`, `student` |
| `employer` | string | لا | اسم جهة العمل (حد أقصى 255 حرف) |
| `income` | decimal | لا | الدخل الشهري (حد أقصى 9999999999.99) |
| `rating` | string | لا | التقييم: `excellent`, `good`, `fair`, `poor` |
| `notes` | text | لا | ملاحظات عن المستأجر |

**مثال Request (cURL):**
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
    "user_id": ["حقل معرف المستخدم مطلوب."],
    "user_id.exists": ["المعرف المحدد غير صالح."],
    "user_id.unique": ["معرف المستخدم موجود مسبقاً."]
  }
}
```

---

### تحديث مستأجر

**Endpoint:** `PUT /api/v1/tenants/{tenant}` أو `PATCH /api/v1/tenants/{tenant}`

**الوصف:** تحديث مستأجر موجود. جميع الحقول اختيارية. سيتم تحديث الحقول المقدمة فقط.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: multipart/form-data
```

**Path Parameters:**

| المعامل | النوع | مطلوب | الوصف |
|---------|------|-------|--------|
| `tenant` | integer | نعم | معرف المستأجر |

**Request Body:** نفس Create Tenant، لكن جميع الحقول اختيارية.

**ملاحظات مهمة:**
- أرسل فقط الحقول التي تريد تحديثها
- رفع صورة جديدة يحل محل الصورة الموجودة
- لا يمكن تغيير معرف الملكية
- يمكن تغيير معرف المستخدم (يجب أن يكون فريداً)

**مثال Request:**
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

### حذف مستأجر

**Endpoint:** `DELETE /api/v1/tenants/{tenant}`

**الوصف:** حذف مستأجر حسب ID.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Path Parameters:**

| المعامل | النوع | مطلوب | الوصف |
|---------|------|-------|--------|
| `tenant` | integer | نعم | معرف المستأجر |

**مثال Request:**
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

**ملاحظة:** سيتم حذف ملفات الوسائط المرتبطة أيضاً. تأكد من عدم وجود عقود نشطة للمستأجر قبل الحذف.

---

## نماذج البيانات

### كائن Tenant

```typescript
interface Tenant {
  id: number;
  user: User | null;
  ownership: Ownership;
  national_id: string | null;
  id_type: 'national_id' | 'iqama' | 'passport' | 'commercial_registration' | null;
  id_document: string | null; // حقل قديم
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

### كائن MediaFile

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

## معالجة الأخطاء

### Response الأخطاء القياسي

```json
{
  "success": false,
  "message": "مفتاح رسالة الخطأ",
  "errors": {
    "field_name": ["رسالة الخطأ 1", "رسالة الخطأ 2"]
  }
}
```

### أكواد الأخطاء الشائعة

| كود الحالة | الوصف |
|------------|-------|
| 200 | نجاح |
| 201 | تم الإنشاء |
| 400 | طلب خاطئ (أخطاء التحقق) |
| 401 | غير مصرح (token غير صالح أو مفقود) |
| 403 | ممنوع (لا توجد صلاحية) |
| 404 | غير موجود |
| 422 | كيان غير قابل للمعالجة |
| 500 | خطأ في الخادم |

### رسائل الأخطاء الشائعة

- `messages.errors.ownership_required` - نطاق الملكية مطلوب
- `messages.errors.not_found` - المورد غير موجود
- `messages.errors.unauthorized` - token المصادقة غير صالح أو مفقود
- `messages.errors.forbidden` - لا توجد صلاحية للوصول إلى المورد
- `messages.validation.required` - الحقل مطلوب
- `messages.validation.exists` - قيمة الحقل غير موجودة
- `messages.validation.unique` - قيمة الحقل موجودة مسبقاً

---

## أمثلة

### مثال تكامل Frontend (JavaScript/React)

```javascript
// تسجيل الدخول
async function login(email, password) {
  const response = await fetch('http://localhost:8000/api/v1/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include', // مهم: تضمين الكوكيز
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

// إنشاء مستأجر مع رفع الملفات
async function createTenant(tenantData, files) {
  const formData = new FormData();
  
  // إضافة الحقول النصية
  formData.append('user_id', tenantData.user_id);
  formData.append('national_id', tenantData.national_id || '');
  formData.append('id_type', tenantData.id_type || '');
  formData.append('id_expiry', tenantData.id_expiry || '');
  
  // إضافة الملفات
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
      // لا تحدد Content-Type - المتصفح سيحدده مع boundary
    },
    credentials: 'include', // مهم: تضمين الكوكيز لنطاق الملكية
    body: formData
  });
  
  return await response.json();
}

// الحصول على قائمة المستأجرين
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

// تحديث مستأجر
async function updateTenant(tenantId, updates, files = {}) {
  const formData = new FormData();
  
  // إضافة فقط الحقول التي يتم تحديثها
  Object.keys(updates).forEach(key => {
    if (updates[key] !== null && updates[key] !== undefined) {
      formData.append(key, updates[key]);
    }
  });
  
  // إضافة الملفات إذا تم توفيرها
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

// حذف مستأجر
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

مجموعة Postman كاملة متاحة في:
`docs/postman/Tenant_Module_API.postman_collection.json`

**للاستخدام:**
1. استورد المجموعة إلى Postman
2. قم بإعداد متغيرات البيئة:
   - `base_url`: رابط API الأساسي (مثلاً `http://localhost:8000`)
   - `access_token`: سيتم تعيينه تلقائياً بعد تسجيل الدخول
3. قم بتشغيل طلب Login أولاً
4. جميع الطلبات الأخرى ستستخدم access token تلقائياً

---

## ملاحظات لمطوري Frontend

1. **المصادقة:**
   - دائماً أضف `credentials: 'include'` في طلبات fetch للتعامل مع الكوكيز
   - احفظ access token في localStorage أو state
   - Refresh token يتم إدارته تلقائياً عبر httpOnly cookies

2. **رفع الملفات:**
   - استخدم `FormData` لطلبات create/update مع الملفات
   - لا تحدد header `Content-Type` يدوياً - المتصفح سيحدده مع boundary
   - صيغ الصور المدعومة: jpg, jpeg, png, gif
   - الملفات يتم تحسينها وتغيير حجمها تلقائياً

3. **نطاق الملكية:**
   - معرف الملكية يتم أخذه تلقائياً من الكوكي
   - جميع عمليات المستأجرين محدودة بالملكية الحالية
   - لا حاجة لإرسال ownership_id في الطلبات

4. **معالجة الأخطاء:**
   - دائماً تحقق من حقل `success` في الـ response
   - اعرض أخطاء التحقق من كائن `errors`
   - تعامل مع أخطاء 401 بإعادة التوجيه إلى تسجيل الدخول

5. **التصفح (Pagination):**
   - استخدم `per_page=-1` للحصول على جميع المستأجرين
   - بيانات التصفح في كائن `meta`
   - حجم الصفحة الافتراضي هو 15

---

## الدعم

للأسئلة أو المشاكل، يرجى الاتصال بفريق تطوير Backend.

**آخر تحديث:** 18 ديسمبر 2025

