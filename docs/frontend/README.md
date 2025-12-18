# Frontend Development Resources - Tenant Invitations Feature

## نظرة عامة

هذا المجلد يحتوي على جميع الموارد المطلوبة لمطوري Frontend لتطوير واجهة المستخدم لنظام دعوات المستأجرين.

---

## الملفات المتوفرة

### 1. 📋 [TENANT_INVITATIONS_FRONTEND_TASK.md](./TENANT_INVITATIONS_FRONTEND_TASK.md)
**الوصف:** مهمة تطوير شاملة باللغة العربية لمطوري React

**المحتوى:**
- المتطلبات الوظيفية الكاملة
- المتطلبات التقنية
- حالات الاستخدام (Use Cases)
- متطلبات التصميم
- معايير الجودة
- الجدول الزمني المقترح
- Checklist للتسليم

**استخدمه:** كدليل رئيسي لتطوير الواجهة

---

### 2. 🧪 [TENANT_INVITATIONS_API_TEST_CASES.md](./TENANT_INVITATIONS_API_TEST_CASES.md)
**الوصف:** جميع حالات الاختبار والأمثلة الكاملة للـ API

**المحتوى:**
- Test cases لجميع الـ endpoints
- أمثلة على الـ requests والـ responses
- حالات الأخطاء (Error scenarios)
- Edge cases
- Complete flow examples
- Testing checklist

**استخدمه:** كمرجع لاختبار جميع السيناريوهات

---

### 3. 📮 [Postman Collection](./Tenant_Invitations_API.postman_collection.json)
**الوصف:** Postman collection كامل مع جميع الـ endpoints

**المحتوى:**
- جميع الـ API endpoints
- أمثلة على الـ requests
- Test scripts تلقائية
- Documentation مدمج

**استخدمه:** لاختبار الـ API مباشرة في Postman

**الموقع:** `docs/postman/Tenant_Invitations_API.postman_collection.json`

-
## البدء السريع

### الخطوة 1: فهم المتطلبات
1. اقرأ [TENANT_INVITATIONS_FRONTEND_TASK.md](./TENANT_INVITATIONS_FRONTEND_TASK.md)
2. راجع حالات الاستخدام (Use Cases)
3. فهم المتطلبات الوظيفية

### الخطوة 2: إعداد البيئة
1. استورد Postman Collection
2. قم بتسجيل الدخول للحصول على access token
3. اختبر الـ endpoints الأساسية

### الخطوة 3: البدء في التطوير
1. ابدأ بإعداد المشروع (React setup)
2. إعداد API client
3. تطوير الصفحات حسب الأولوية

### الخطوة 4: الاختبار
1. استخدم [TENANT_INVITATIONS_API_TEST_CASES.md](./TENANT_INVITATIONS_API_TEST_CASES.md)
2. اختبر جميع السيناريوهات
3. تأكد من معالجة الأخطاء

---

## هيكل المشروع المقترح

```
frontend/
├── src/
│   ├── api/
│   │   ├── auth.js          # Authentication API
│   │   ├── invitations.js   # Invitations API
│   │   └── client.js        # Axios/Fetch setup
│   ├── components/
│   │   ├── invitations/
│   │   │   ├── InvitationList.jsx
│   │   │   ├── InvitationForm.jsx
│   │   │   ├── BulkInvitationForm.jsx
│   │   │   └── InvitationDetails.jsx
│   │   └── registration/
│   │       ├── RegistrationForm.jsx
│   │       └── TokenValidation.jsx
│   ├── pages/
│   │   ├── owner/
│   │   │   └── InvitationsPage.jsx
│   │   └── public/
│   │       └── TenantRegistrationPage.jsx
│   ├── hooks/
│   │   ├── useInvitations.js
│   │   └── useNotifications.js
│   ├── store/              # Redux/Zustand
│   ├── utils/
│   └── constants/
└── ...
```

---

## API Endpoints Summary

### Owner Endpoints (مطلوب مصادقة)
- `GET /api/v1/tenants/invitations` - قائمة الدعوات
- `POST /api/v1/tenants/invitations` - إنشاء دعوة واحدة
- `POST /api/v1/tenants/invitations/bulk` - إنشاء دعوات متعددة
- `POST /api/v1/tenants/invitations/generate-link` - إنشاء رابط دعوة
- `GET /api/v1/tenants/invitations/{uuid}` - عرض تفاصيل الدعوة
- `POST /api/v1/tenants/invitations/{uuid}/resend` - إعادة إرسال
- `POST /api/v1/tenants/invitations/{uuid}/cancel` - إلغاء الدعوة

### Public Endpoints (بدون مصادقة)
- `GET /api/v1/public/tenant-invitations/{token}/validate` - التحقق من الرمز
- `POST /api/v1/public/tenant-invitations/{token}/accept` - قبول الدعوة والتسجيل

---

## Environment Variables

```env
REACT_APP_API_URL=http://localhost:8000
REACT_APP_REVERB_APP_KEY=your-key
REACT_APP_REVERB_HOST=localhost
REACT_APP_REVERB_PORT=8080
REACT_APP_FRONTEND_URL=http://localhost:3000
```

---

## Real-time Notifications

### Setup Laravel Echo
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: process.env.REACT_APP_REVERB_APP_KEY,
    wsHost: process.env.REACT_APP_REVERB_HOST,
    wsPort: process.env.REACT_APP_REVERB_PORT,
    wssPort: process.env.REACT_APP_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});
```

### Listen to Notifications
```javascript
Echo.private(`user.${userId}`)
    .notification((notification) => {
        if (notification.category === 'tenant_invitation') {
            // Handle notification
        }
    });
```

---

## Important Notes

### 1. Authentication
- استخدم Bearer Token في Authorization header
- احفظ Token في secure storage
- أدار Refresh Token تلقائياً

### 2. Ownership Scope
- استخدم Cookie `ownership_uuid` للتعامل مع الملكية
- تأكد من إرسال Cookie مع كل request

### 3. Error Handling
- عالج جميع حالات الأخطاء
- أظهر رسائل واضحة للمستخدم
- سجل الأخطاء للـ debugging

### 4. Validation
- تحقق من البيانات قبل الإرسال
- استخدم validation library (Yup/Zod)
- أظهر رسائل خطأ واضحة

### 5. Real-time
- أعد إعداد Laravel Echo
- استمع للإشعارات في الوقت الفعلي
- حدث الواجهة تلقائياً

---

## Support & Resources

### Backend Documentation
- [Private Documentation](../../private/tenant-invitations/)
- [API Endpoints - Owner](../../private/tenant-invitations/03-api-endpoints-owner.md)
- [API Endpoints - Public](../../private/tenant-invitations/04-api-endpoints-public.md)
- [Workflows](../../private/tenant-invitations/05-workflow-owner.md)

### Testing
- [Postman Collection](../../postman/Tenant_Invitations_API.postman_collection.json)
- [Test Cases](./TENANT_INVITATIONS_API_TEST_CASES.md)

### Questions?
- للأسئلة التقنية: راجع التوثيق الخاص
- للـ API Issues: راجع Test Cases
- للمشاكل: راجع Troubleshooting في Private Docs

---

## Checklist قبل البدء

- [ ] قرأت Frontend Task Document
- [ ] فهمت جميع Use Cases
- [ ] استوردت Postman Collection
- [ ] اختبرت الـ API endpoints
- [ ] أعددت بيئة التطوير
- [ ] فهمت Authentication flow
- [ ] فهمت Real-time setup
- [ ] راجعت Error scenarios

---

**تاريخ الإنشاء:** 15 ديسمبر 2025  
**الحالة:** ✅ جاهز للبدء  
**الأولوية:** عالية

