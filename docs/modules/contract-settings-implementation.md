# توثيق إعدادات العقود - Contract Settings Implementation

## نظرة عامة

تم تطبيق نظام إعدادات شامل لإدارة العقود بشكل مرن وقابل للتخصيص. جميع الإعدادات قابلة للتعديل من خلال لوحة التحكم وتطبق تلقائياً على جميع العمليات المتعلقة بالعقود.

---

## 📋 قائمة الإعدادات

### 1. إعدادات الوحدات (Units Settings)

#### `default_unit_rent_frequency`
- **الوصف**: تردد الإيجار الافتراضي للوحدات (سنوي/شهري/ربع سنوي)
- **القيمة الافتراضية**: `yearly`
- **القيم المتاحة**: `yearly`, `monthly`, `quarterly`
- **الاستخدام**: يستخدم في الواجهة الأمامية لملء حقل تردد الإيجار تلقائياً (يمكن تعديله يدوياً)
- **المكان**: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 330)

---

### 2. إعدادات إنشاء العقود (Contract Creation Settings)

#### `default_contract_status`
- **الوصف**: الحالة الافتراضية عند إنشاء عقد جديد
- **القيمة الافتراضية**: `draft`
- **القيم المتاحة**: `draft`, `pending`, `active`, `expired`, `terminated`, `cancelled`
- **الاستخدام**: يتم تطبيقه تلقائياً عند إنشاء عقد جديد إذا لم يتم تحديد الحالة
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 333)
  - التطبيق: `app/Services/V1/Contract/ContractService.php` - Method `applyDefaultSettings()` (السطر 155)

#### `default_payment_frequency`
- **الوصف**: تردد الدفع الافتراضي للعقود
- **القيمة الافتراضية**: `monthly`
- **القيم المتاحة**: `monthly`, `quarterly`, `yearly`, `weekly`
- **الاستخدام**: يتم تطبيقه تلقائياً عند إنشاء عقد جديد إذا لم يتم تحديد تردد الدفع
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 280)
  - التطبيق: `app/Services/V1/Contract/ContractService.php` - Method `applyDefaultSettings()` (السطر 160)

#### `default_contract_duration_months`
- **الوصف**: مدة العقد الافتراضية بالأشهر
- **القيمة الافتراضية**: `12` (سنة واحدة)
- **الاستخدام**: يستخدم كمرجع في الواجهة الأمامية
- **المكان**: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 280)

#### `require_ejar_code`
- **الوصف**: إلزامية كود إيجار (منصة إيجار السعودية)
- **القيمة الافتراضية**: `false` (0)
- **الاستخدام**: إذا كان `true`، يجب إدخال كود إيجار عند إنشاء عقد جديد
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 336)
  - التطبيق: `app/Http/Requests/V1/Contract/StoreContractRequest.php` - Method `withValidator()` (السطر 145)

#### `allow_backdated_contracts`
- **الوصف**: السماح بإنشاء عقود بتاريخ سابق
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `false`، لا يمكن إنشاء عقد بتاريخ بداية في الماضي
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 339)
  - التطبيق: `app/Http/Requests/V1/Contract/StoreContractRequest.php` - Method `withValidator()` (السطر 150)

#### `min_contract_duration_months`
- **الوصف**: الحد الأدنى لمدة العقد بالأشهر
- **القيمة الافتراضية**: `1`
- **الاستخدام**: التحقق من أن مدة العقد لا تقل عن هذه القيمة
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 342)
  - التطبيق: `app/Http/Requests/V1/Contract/StoreContractRequest.php` - Method `withValidator()` (السطر 163)

#### `max_contract_duration_months`
- **الوصف**: الحد الأقصى لمدة العقد بالأشهر
- **القيمة الافتراضية**: `120` (10 سنوات)
- **الاستخدام**: التحقق من أن مدة العقد لا تزيد عن هذه القيمة
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 345)
  - التطبيق: `app/Http/Requests/V1/Contract/StoreContractRequest.php` - Method `withValidator()` (السطر 163)

#### `max_units_per_contract`
- **الوصف**: الحد الأقصى لعدد الوحدات في عقد واحد
- **القيمة الافتراضية**: `10`
- **الاستخدام**: التحقق من أن عدد الوحدات في العقد لا يتجاوز هذه القيمة
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 393)
  - التطبيق: 
    - `app/Services/V1/Contract/ContractService.php` - Method `create()` (السطر 95)
    - `app/Services/V1/Contract/ContractService.php` - Method `update()` (السطر 241)
    - `app/Http/Requests/V1/Contract/StoreContractRequest.php` - Method `withValidator()` (السطر 178)

---

### 3. إعدادات الموافقة (Approval Settings)

#### `contract_approval_required`
- **الوصف**: إلزامية الموافقة قبل تفعيل العقد
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `true`، يجب الموافقة على العقد قبل تفعيله
- **المكان**: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 273)

---

### 4. إعدادات الحسابات المالية (Financial Calculation Settings)

#### `contract_vat_percentage`
- **الوصف**: نسبة ضريبة القيمة المضافة للعقود
- **القيمة الافتراضية**: `15.00` (15% - نسبة السعودية)
- **الاستخدام**: حساب `vat_amount` تلقائياً بناءً على هذه النسبة
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 396)
  - التطبيق: 
    - `app/Services/V1/Contract/ContractSettingService.php` - Method `getContractVatPercentage()` (السطر 70)
    - `app/Services/V1/Contract/ContractSettingService.php` - Method `calculateVatAmount()` (السطر 300)

#### `auto_calculate_contract_rent`
- **الوصف**: حساب إيجار العقد تلقائياً من مجموع إيجارات الوحدات
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `true`، يتم حساب `base_rent` تلقائياً من مجموع `rent_amount` للوحدات
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 384)
  - التطبيق: `app/Services/V1/Contract/ContractService.php` - Method `applyFinancialCalculations()` (السطر 172)

#### `auto_calculate_total_rent`
- **الوصف**: حساب إجمالي الإيجار تلقائياً (base_rent + fees + VAT)
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `true`، يتم حساب `total_rent` و `vat_amount` تلقائياً
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 387)
  - التطبيق: 
    - `app/Services/V1/Contract/ContractService.php` - Method `applyFinancialCalculations()` (السطر 183)
    - `app/Services/V1/Contract/ContractService.php` - Method `applyFinancialCalculationsForUpdate()` (السطر 520)

---

### 5. إعدادات التعديل (Editing Settings)

#### `allow_edit_active_contracts`
- **الوصف**: السماح بتعديل العقود النشطة
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `false`، لا يمكن تعديل العقود التي حالتها `active`
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 354)
  - التطبيق: `app/Services/V1/Contract/ContractService.php` - Method `validateEditingPermissions()` (السطر 470)

#### `allow_edit_contract_dates`
- **الوصف**: السماح بتعديل تواريخ العقد (start/end)
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `false`، لا يمكن تعديل تواريخ بداية أو نهاية العقد
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 357)
  - التطبيق: `app/Services/V1/Contract/ContractService.php` - Method `validateEditingPermissions()` (السطر 477)

#### `allow_edit_contract_rent`
- **الوصف**: السماح بتعديل مبالغ الإيجار
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `false`، لا يمكن تعديل أي من حقول الإيجار (rent, base_rent, rent_fees, vat_amount, total_rent)
- **المكان**: 
  - الـ Seeder: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 360)
  - التطبيق: `app/Services/V1/Contract/ContractService.php` - Method `validateEditingPermissions()` (السطر 485)

---

### 6. إعدادات انتهاء العقد (Contract Expiry Settings)

#### `auto_expire_contracts`
- **الوصف**: انتهاء تلقائي للعقود عند انتهاء تاريخ العقد
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `true`، يتم تغيير حالة العقد إلى `expired` تلقائياً عند انتهاء تاريخ العقد
- **المكان**: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 348)

#### `contract_renewal_grace_period_days`
- **الوصف**: فترة السماح لتجديد العقد بعد انتهائه (بالأيام)
- **القيمة الافتراضية**: `30`
- **الاستخدام**: عدد الأيام المسموح بها بعد انتهاء العقد لتجديده قبل اعتباره منتهياً نهائياً
- **المكان**: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 351)

#### `auto_release_units_on_expiry`
- **الوصف**: تحرير الوحدات تلقائياً عند انتهاء العقد
- **القيمة الافتراضية**: `true` (1)
- **الاستخدام**: إذا كان `true`، يتم تغيير حالة الوحدات إلى `available` تلقائياً عند انتهاء العقد
- **المكان**: `database/seeders/V1/Setting/SystemSettingSeeder.php` (السطر 363)

---

## 🏗️ البنية المعمارية (Architecture)

### 1. ContractSettingService
**الموقع**: `app/Services/V1/Contract/ContractSettingService.php`

**الوصف**: Service class مركزي لإدارة جميع إعدادات العقود بشكل OOP.

**الوظائف الرئيسية**:
- `getDefaultUnitRentFrequency()` - الحصول على تردد الإيجار الافتراضي للوحدات
- `getDefaultContractStatus()` - الحصول على الحالة الافتراضية للعقد
- `getDefaultPaymentFrequency()` - الحصول على تردد الدفع الافتراضي
- `getContractVatPercentage()` - الحصول على نسبة VAT
- `calculateVatAmount()` - حساب مبلغ VAT
- `calculateTotalRent()` - حساب إجمالي الإيجار
- `isContractApprovalRequired()` - التحقق من إلزامية الموافقة
- `isEjarCodeRequired()` - التحقق من إلزامية كود إيجار
- `areBackdatedContractsAllowed()` - التحقق من السماح بالعقود المؤرخة سابقاً
- `getMinContractDurationMonths()` - الحصول على الحد الأدنى للمدة
- `getMaxContractDurationMonths()` - الحصول على الحد الأقصى للمدة
- `getMaxUnitsPerContract()` - الحصول على الحد الأقصى للوحدات
- `isAutoCalculateContractRentEnabled()` - التحقق من تفعيل الحساب التلقائي للإيجار
- `isAutoCalculateTotalRentEnabled()` - التحقق من تفعيل الحساب التلقائي للإجمالي
- `isEditingActiveContractsAllowed()` - التحقق من السماح بتعديل العقود النشطة
- `isEditingContractDatesAllowed()` - التحقق من السماح بتعديل التواريخ
- `isEditingContractRentAllowed()` - التحقق من السماح بتعديل الإيجار

---

### 2. ContractService
**الموقع**: `app/Services/V1/Contract/ContractService.php`

**التعديلات**:
- تم حقن `ContractSettingService` في Constructor (السطر 26)
- Method `create()` - تطبيق الإعدادات الافتراضية والحسابات المالية (السطر 81)
- Method `update()` - التحقق من صلاحيات التعديل وتطبيق الحسابات المالية (السطر 222)
- Method `applyDefaultSettings()` - تطبيق الإعدادات الافتراضية (السطر 154)
- Method `applyFinancialCalculations()` - تطبيق الحسابات المالية عند الإنشاء (السطر 168)
- Method `applyFinancialCalculationsForUpdate()` - تطبيق الحسابات المالية عند التحديث (السطر 510)
- Method `validateEditingPermissions()` - التحقق من صلاحيات التعديل (السطر 465)

---

### 3. StoreContractRequest
**الموقع**: `app/Http/Requests/V1/Contract/StoreContractRequest.php`

**التعديلات**:
- تم إضافة `withValidator()` method للتحقق من الإعدادات (السطر 139)
- التحقق من إلزامية كود إيجار (`require_ejar_code`)
- التحقق من السماح بالعقود المؤرخة سابقاً (`allow_backdated_contracts`)
- التحقق من الحد الأدنى/الأقصى لمدة العقد (`min_contract_duration_months` / `max_contract_duration_months`)
- التحقق من الحد الأقصى لعدد الوحدات (`max_units_per_contract`)

---

### 4. UpdateContractRequest
**الموقع**: `app/Http/Requests/V1/Contract/UpdateContractRequest.php`

**التعديلات**:
- تم إضافة `withValidator()` method للتحقق من الإعدادات (السطر 139)
- التحقق من الحد الأدنى/الأقصى لمدة العقد عند تحديث التواريخ
- التحقق من الحد الأقصى لعدد الوحدات

---

## 🔄 تدفق العمل (Workflow)

### عند إنشاء عقد جديد:

1. **التحقق من الإعدادات في Request** (`StoreContractRequest::withValidator()`):
   - التحقق من إلزامية كود إيجار
   - التحقق من السماح بالعقود المؤرخة سابقاً
   - التحقق من مدة العقد (الحد الأدنى/الأقصى)
   - التحقق من عدد الوحدات (الحد الأقصى)

2. **تطبيق الإعدادات الافتراضية** (`ContractService::applyDefaultSettings()`):
   - تطبيق الحالة الافتراضية (`default_contract_status`)
   - تطبيق تردد الدفع الافتراضي (`default_payment_frequency`)

3. **تطبيق الحسابات المالية** (`ContractService::applyFinancialCalculations()`):
   - حساب `base_rent` من مجموع إيجارات الوحدات (إذا كان `auto_calculate_contract_rent` مفعّل)
   - حساب `vat_amount` من `contract_vat_percentage` (إذا كان `auto_calculate_total_rent` مفعّل)
   - حساب `total_rent` = `base_rent` + `rent_fees` + `vat_amount`

4. **التحقق من عدد الوحدات** (`ContractService::create()`):
   - التحقق من أن عدد الوحدات لا يتجاوز `max_units_per_contract`

---

### عند تحديث عقد:

1. **التحقق من صلاحيات التعديل** (`ContractService::validateEditingPermissions()`):
   - التحقق من السماح بتعديل العقود النشطة (`allow_edit_active_contracts`)
   - التحقق من السماح بتعديل التواريخ (`allow_edit_contract_dates`)
   - التحقق من السماح بتعديل الإيجار (`allow_edit_contract_rent`)

2. **التحقق من الإعدادات في Request** (`UpdateContractRequest::withValidator()`):
   - التحقق من مدة العقد عند تحديث التواريخ
   - التحقق من عدد الوحدات

3. **تطبيق الحسابات المالية** (`ContractService::applyFinancialCalculationsForUpdate()`):
   - إعادة حساب `vat_amount` و `total_rent` إذا تم تحديث `base_rent` أو `rent_fees`

---

## 📝 أمثلة الاستخدام

### مثال 1: حساب VAT تلقائياً

```php
// في ContractService
$vatAmount = $this->contractSettingService->calculateVatAmount(
    $baseRent + $rentFees,
    $ownershipId
);
// النتيجة: إذا كان base_rent = 10000 و rent_fees = 500 و VAT = 15%
// vatAmount = (10000 + 500) * 0.15 = 1575
```

### مثال 2: حساب إجمالي الإيجار

```php
// في ContractService
$totalRent = $this->contractSettingService->calculateTotalRent(
    $baseRent,
    $rentFees,
    $ownershipId
);
// النتيجة: total_rent = base_rent + rent_fees + vat_amount
```

### مثال 3: التحقق من صلاحيات التعديل

```php
// في ContractService::validateEditingPermissions()
if ($contract->status === 'active' && 
    !$this->contractSettingService->isEditingActiveContractsAllowed($ownershipId)) {
    throw ValidationException::withMessages([
        'status' => 'Cannot edit active contracts'
    ]);
}
```

---

## 🎯 ملخص النقاط الرئيسية

1. **جميع الإعدادات قابلة للتخصيص**: يمكن تعديل أي إعداد من خلال لوحة التحكم
2. **تطبيق تلقائي**: الإعدادات تطبق تلقائياً دون الحاجة لتعديل الكود
3. **بنية OOP نظيفة**: استخدام Service Pattern لفصل المسؤوليات
4. **التحقق في عدة مستويات**: التحقق في Request و Service
5. **حسابات مالية ذكية**: حساب تلقائي للـ VAT و total_rent بناءً على الإعدادات
6. **مرونة في التعديل**: إمكانية التحكم في ما يمكن تعديله وما لا يمكن

---

## 📚 الملفات المتأثرة

1. `database/seeders/V1/Setting/SystemSettingSeeder.php` - تعريف الإعدادات
2. `app/Services/V1/Contract/ContractSettingService.php` - Service لإدارة الإعدادات
3. `app/Services/V1/Contract/ContractService.php` - تطبيق الإعدادات في منطق الأعمال
4. `app/Http/Requests/V1/Contract/StoreContractRequest.php` - التحقق من الإعدادات عند الإنشاء
5. `app/Http/Requests/V1/Contract/UpdateContractRequest.php` - التحقق من الإعدادات عند التحديث

---

## ✅ الخلاصة

تم تطبيق نظام إعدادات شامل ومتكامل لإدارة العقود بشكل مرن وقابل للتخصيص. جميع الإعدادات مطبقة بشكل OOP نظيف مع فصل واضح للمسؤوليات. النظام يدعم:

- ✅ حساب تلقائي للحقول المالية
- ✅ تطبيق الإعدادات الافتراضية
- ✅ التحقق من القيود والصلاحيات
- ✅ مرونة في التعديل والتحكم

---

**تاريخ الإنشاء**: 2025-01-18  
**آخر تحديث**: 2025-01-18

