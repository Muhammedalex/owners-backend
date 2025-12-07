# Property Structure Tables Documentation

## نظرة عامة (Overview)

هذا المستند يشرح بالتفصيل جداول هيكل الملكية في النظام. الهيكل يتكون من:
- **محفظة (Portfolio)**: المستوى الأعلى لتجميع الممتلكات
- **مبنى (Building)**: المبنى الفعلي الذي يحتوي على الطوابق والوحدات
- **طابق (Floor)**: الطوابق داخل المبنى
- **وحدة (Unit)**: الوحدات السكنية أو التجارية القابلة للتأجير

---

## 1. جدول Portfolios (المحافظ)

### الوصف
جدول المحافظ يمثل المستوى الأعلى في هيكل الملكية. المحفظة هي مجموعة من المباني أو الممتلكات التي تنتمي لنفس الملكية. يمكن أن تكون المحفظة متداخلة (parent-child relationship) لإنشاء هيكل هرمي.

### الحقول (Fields)

| الحقل | النوع | الوصف | ملاحظات |
|-------|------|-------|---------|
| `id` | bigint | المعرف الفريد | Primary Key, Auto Increment |
| `ownership_id` | bigint | معرف الملكية | Foreign Key → `ownerships.id`, NOT NULL |
| `parent_id` | bigint | معرف المحفظة الأب | Foreign Key → `portfolios.id`, NULL (للمحافظ المتداخلة) |
| `name` | varchar(255) | اسم المحفظة | NOT NULL, مثال: "محفظة الرياض" |
| `code` | varchar(50) | كود المحفظة | NOT NULL, UNIQUE, مثال: "PORT-001" |
| `type` | varchar(50) | نوع المحفظة | DEFAULT 'general', مثال: 'residential', 'commercial', 'mixed' |
| `description` | text | وصف المحفظة | NULL, تفاصيل إضافية عن المحفظة |
| `area` | decimal(12,2) | المساحة الإجمالية | NULL, بالـ متر المربع |
| `active` | boolean | حالة التفعيل | DEFAULT true, false = محفظة معطلة |
| `created_at` | timestamp | تاريخ الإنشاء | تلقائي |
| `updated_at` | timestamp | تاريخ آخر تحديث | تلقائي |

### العلاقات (Relationships)
- **Belongs To**: `ownerships` (ownership_id) - المحفظة تنتمي لملكية واحدة
- **Has Many**: `portfolios` (parent_id) - المحفظة يمكن أن تحتوي على محافظ فرعية
- **Has Many**: `buildings` - المحفظة تحتوي على مباني متعددة
- **Has Many**: `portfolio_locations` - المحفظة يمكن أن تحتوي على مواقع متعددة

### Indexes
- `ownership_id` - للبحث السريع عن محافظ ملكية معينة
- `parent_id` - للبحث السريع عن المحافظ الفرعية
- `code` (UNIQUE) - لضمان عدم تكرار الكود
- `type` - للتصفية حسب نوع المحفظة
- `active` - للتصفية حسب حالة التفعيل

### أمثلة على الاستخدام
```php
// إنشاء محفظة جديدة
Portfolio::create([
    'ownership_id' => 1,
    'name' => 'محفظة الرياض',
    'code' => 'PORT-RIYADH-001',
    'type' => 'residential',
    'area' => 50000.00,
]);

// إنشاء محفظة فرعية
Portfolio::create([
    'ownership_id' => 1,
    'parent_id' => 1, // محفظة الرياض
    'name' => 'مجمع النور',
    'code' => 'PORT-NOOR-001',
    'type' => 'residential',
]);
```

---

## 2. جدول Portfolio Locations (مواقع المحافظ)

### الوصف
جدول مواقع المحافظ يخزن معلومات الموقع الجغرافي للمحافظ. المحفظة الواحدة يمكن أن تحتوي على مواقع متعددة، ولكن موقع واحد فقط يمكن أن يكون أساسي (primary).

### الحقول (Fields)

| الحقل | النوع | الوصف | ملاحظات |
|-------|------|-------|---------|
| `id` | bigint | المعرف الفريد | Primary Key, Auto Increment |
| `portfolio_id` | bigint | معرف المحفظة | Foreign Key → `portfolios.id`, NOT NULL |
| `street` | varchar(255) | اسم الشارع | NULL, مثال: "شارع الملك فهد" |
| `city` | varchar(100) | المدينة | NULL, مثال: "الرياض" |
| `state` | varchar(100) | المنطقة/المنطقة | NULL, مثال: "منطقة الرياض" |
| `country` | varchar(100) | الدولة | DEFAULT 'Saudi Arabia' |
| `zip_code` | varchar(20) | الرمز البريدي | NULL |
| `latitude` | decimal(10,8) | خط العرض | NULL, للإحداثيات الجغرافية |
| `longitude` | decimal(11,8) | خط الطول | NULL, للإحداثيات الجغرافية |
| `primary` | boolean | موقع أساسي | DEFAULT false, true = الموقع الأساسي للمحفظة |

### العلاقات (Relationships)
- **Belongs To**: `portfolios` (portfolio_id) - الموقع ينتمي لمحفظة واحدة

### Indexes
- `portfolio_id` - للبحث السريع عن مواقع محفظة معينة
- `city` - للتصفية حسب المدينة
- `primary` - للبحث عن الموقع الأساسي
- UNIQUE(`portfolio_id`, `primary`) - لضمان وجود موقع أساسي واحد فقط لكل محفظة

### أمثلة على الاستخدام
```php
// إضافة موقع أساسي للمحفظة
PortfolioLocation::create([
    'portfolio_id' => 1,
    'street' => 'شارع الملك فهد',
    'city' => 'الرياض',
    'state' => 'منطقة الرياض',
    'country' => 'Saudi Arabia',
    'zip_code' => '12345',
    'latitude' => 24.7136,
    'longitude' => 46.6753,
    'primary' => true,
]);
```

---

## 3. جدول Buildings (المباني)

### الوصف
جدول المباني يمثل المبنى الفعلي الذي يحتوي على الطوابق والوحدات. المبنى ينتمي لمحفظة ويمكن أن يكون متداخل (parent-child) لإنشاء مجمعات أو أبراج متعددة.

### الحقول (Fields)

| الحقل | النوع | الوصف | ملاحظات |
|-------|------|-------|---------|
| `id` | bigint | المعرف الفريد | Primary Key, Auto Increment |
| `portfolio_id` | bigint | معرف المحفظة | Foreign Key → `portfolios.id`, NOT NULL |
| `ownership_id` | bigint | معرف الملكية | Foreign Key → `ownerships.id`, NOT NULL |
| `parent_id` | bigint | معرف المبنى الأب | Foreign Key → `buildings.id`, NULL (للمباني المتداخلة) |
| `name` | varchar(255) | اسم المبنى | NOT NULL, مثال: "برج النور" |
| `code` | varchar(50) | كود المبنى | NOT NULL, مثال: "BLD-001" |
| `type` | varchar(50) | نوع المبنى | NOT NULL, مثال: 'residential', 'commercial', 'office', 'mixed' |
| `description` | text | وصف المبنى | NULL, تفاصيل إضافية |
| `street` | varchar(255) | اسم الشارع | NULL |
| `city` | varchar(100) | المدينة | NULL |
| `state` | varchar(100) | المنطقة | NULL |
| `country` | varchar(100) | الدولة | DEFAULT 'Saudi Arabia' |
| `zip_code` | varchar(20) | الرمز البريدي | NULL |
| `latitude` | decimal(10,8) | خط العرض | NULL |
| `longitude` | decimal(11,8) | خط الطول | NULL |
| `floors` | int | عدد الطوابق | DEFAULT 1, إجمالي عدد الطوابق في المبنى |
| `year` | int | سنة البناء | NULL, مثال: 2020 |
| `active` | boolean | حالة التفعيل | DEFAULT true |
| `created_at` | timestamp | تاريخ الإنشاء | تلقائي |
| `updated_at` | timestamp | تاريخ آخر تحديث | تلقائي |

### العلاقات (Relationships)
- **Belongs To**: `portfolios` (portfolio_id) - المبنى ينتمي لمحفظة واحدة
- **Belongs To**: `ownerships` (ownership_id) - المبنى ينتمي لملكية واحدة
- **Has Many**: `buildings` (parent_id) - المبنى يمكن أن يحتوي على مباني فرعية
- **Has Many**: `building_floors` - المبنى يحتوي على طوابق متعددة
- **Has Many**: `units` - المبنى يحتوي على وحدات متعددة

### Indexes
- `portfolio_id` - للبحث السريع عن مباني محفظة معينة
- `ownership_id` - للبحث السريع عن مباني ملكية معينة
- `parent_id` - للبحث السريع عن المباني الفرعية
- `code` - للبحث السريع بالكود
- `type` - للتصفية حسب نوع المبنى
- `active` - للتصفية حسب حالة التفعيل
- `city` - للتصفية حسب المدينة

### أمثلة على الاستخدام
```php
// إنشاء مبنى جديد
Building::create([
    'portfolio_id' => 1,
    'ownership_id' => 1,
    'name' => 'برج النور',
    'code' => 'BLD-NOOR-001',
    'type' => 'residential',
    'street' => 'شارع الملك فهد',
    'city' => 'الرياض',
    'floors' => 10,
    'year' => 2020,
]);

// إنشاء مبنى فرعي (مثل برج في مجمع)
Building::create([
    'portfolio_id' => 1,
    'ownership_id' => 1,
    'parent_id' => 1, // برج النور
    'name' => 'البرج A',
    'code' => 'BLD-NOOR-A',
    'type' => 'residential',
    'floors' => 5,
]);
```

---

## 4. جدول Building Floors (الطوابق)

### الوصف
جدول الطوابق يمثل الطوابق داخل المبنى. كل طابق له رقم واسم اختياري ويمكن أن يحتوي على وحدات متعددة.

### الحقول (Fields)

| الحقل | النوع | الوصف | ملاحظات |
|-------|------|-------|---------|
| `id` | bigint | المعرف الفريد | Primary Key, Auto Increment |
| `building_id` | bigint | معرف المبنى | Foreign Key → `buildings.id`, NOT NULL |
| `number` | int | رقم الطابق | NOT NULL, مثال: 1, 2, 3 (يمكن أن يكون سالب للطوابق السفلية) |
| `name` | varchar(100) | اسم الطابق | NULL, مثال: "الطابق الأرضي", "الطابق الأول" |
| `description` | text | وصف الطابق | NULL, تفاصيل إضافية |
| `units` | int | عدد الوحدات | DEFAULT 0, عدد الوحدات في هذا الطابق |
| `active` | boolean | حالة التفعيل | DEFAULT true |

### العلاقات (Relationships)
- **Belongs To**: `buildings` (building_id) - الطابق ينتمي لمبنى واحد
- **Has Many**: `units` - الطابق يحتوي على وحدات متعددة

### Indexes
- `building_id` - للبحث السريع عن طوابق مبنى معين
- `number` - للترتيب حسب رقم الطابق
- `active` - للتصفية حسب حالة التفعيل
- UNIQUE(`building_id`, `number`) - لضمان عدم تكرار رقم الطابق في نفس المبنى

### ملاحظات مهمة
- رقم الطابق يمكن أن يكون سالب للطوابق السفلية (مثل -1 للبدروم)
- رقم الطابق 0 عادة يمثل الطابق الأرضي
- يجب أن يكون رقم الطابق فريد داخل نفس المبنى

### أمثلة على الاستخدام
```php
// إنشاء طابق جديد
BuildingFloor::create([
    'building_id' => 1,
    'number' => 1,
    'name' => 'الطابق الأول',
    'units' => 4,
]);

// إنشاء طابق أرضي
BuildingFloor::create([
    'building_id' => 1,
    'number' => 0,
    'name' => 'الطابق الأرضي',
    'units' => 2,
]);

// إنشاء طابق سفلي
BuildingFloor::create([
    'building_id' => 1,
    'number' => -1,
    'name' => 'البدروم',
    'units' => 0,
]);
```

---

## 5. جدول Units (الوحدات)

### الوصف
جدول الوحدات يمثل الوحدات السكنية أو التجارية القابلة للتأجير. الوحدة تنتمي لمبنى وطابق ويمكن أن تحتوي على مواصفات إضافية.

### الحقول (Fields)

| الحقل | النوع | الوصف | ملاحظات |
|-------|------|-------|---------|
| `id` | bigint | المعرف الفريد | Primary Key, Auto Increment |
| `building_id` | bigint | معرف المبنى | Foreign Key → `buildings.id`, NOT NULL |
| `floor_id` | bigint | معرف الطابق | Foreign Key → `building_floors.id`, NULL (يمكن أن تكون الوحدة بدون طابق محدد) |
| `ownership_id` | bigint | معرف الملكية | Foreign Key → `ownerships.id`, NOT NULL |
| `number` | varchar(50) | رقم الوحدة | NOT NULL, مثال: "101", "A-201" |
| `type` | varchar(50) | نوع الوحدة | NOT NULL, مثال: 'apartment', 'shop', 'office', 'warehouse' |
| `name` | varchar(255) | اسم الوحدة | NULL, مثال: "شقة فاخرة" |
| `description` | text | وصف الوحدة | NULL, تفاصيل إضافية |
| `area` | decimal(8,2) | مساحة الوحدة | NOT NULL, بالـ متر المربع |
| `price_monthly` | decimal(12,2) | السعر الشهري | NULL, بالريال السعودي |
| `price_quarterly` | decimal(12,2) | السعر الربع سنوي | NULL, بالريال السعودي |
| `price_yearly` | decimal(12,2) | السعر السنوي | NULL, بالريال السعودي |
| `status` | varchar(50) | حالة الوحدة | DEFAULT 'available', مثال: 'available', 'rented', 'maintenance', 'reserved' |
| `active` | boolean | حالة التفعيل | DEFAULT true |
| `created_at` | timestamp | تاريخ الإنشاء | تلقائي |
| `updated_at` | timestamp | تاريخ آخر تحديث | تلقائي |

### العلاقات (Relationships)
- **Belongs To**: `buildings` (building_id) - الوحدة تنتمي لمبنى واحد
- **Belongs To**: `building_floors` (floor_id) - الوحدة تنتمي لطابق واحد (اختياري)
- **Belongs To**: `ownerships` (ownership_id) - الوحدة تنتمي لملكية واحدة
- **Has Many**: `unit_specifications` - الوحدة تحتوي على مواصفات متعددة
- **Has Many**: `contracts` - الوحدة يمكن أن تحتوي على عقود متعددة

### Indexes
- `building_id` - للبحث السريع عن وحدات مبنى معين
- `floor_id` - للبحث السريع عن وحدات طابق معين
- `ownership_id` - للبحث السريع عن وحدات ملكية معينة
- `number` - للبحث السريع برقم الوحدة
- `type` - للتصفية حسب نوع الوحدة
- `status` - للتصفية حسب حالة الوحدة
- `active` - للتصفية حسب حالة التفعيل
- UNIQUE(`building_id`, `number`) - لضمان عدم تكرار رقم الوحدة في نفس المبنى

### حالات الوحدة (Status Values)
- `available` - متاحة للتأجير
- `rented` - مؤجرة
- `maintenance` - تحت الصيانة
- `reserved` - محجوزة
- `sold` - مباعة (إذا كانت للبيع)

### أمثلة على الاستخدام
```php
// إنشاء وحدة سكنية
Unit::create([
    'building_id' => 1,
    'floor_id' => 1,
    'ownership_id' => 1,
    'number' => '101',
    'type' => 'apartment',
    'name' => 'شقة فاخرة',
    'area' => 120.50,
    'price_monthly' => 5000.00,
    'price_quarterly' => 14000.00,
    'price_yearly' => 55000.00,
    'status' => 'available',
]);

// إنشاء وحدة تجارية
Unit::create([
    'building_id' => 1,
    'floor_id' => 0, // الطابق الأرضي
    'ownership_id' => 1,
    'number' => 'SHOP-01',
    'type' => 'shop',
    'name' => 'محل تجاري',
    'area' => 50.00,
    'price_monthly' => 8000.00,
    'status' => 'available',
]);
```

---

## 6. جدول Unit Specifications (مواصفات الوحدات)

### الوصف
جدول مواصفات الوحدات يخزن مواصفات إضافية للوحدات بشكل مرن (key-value pairs). هذا يسمح بإضافة مواصفات مختلفة لكل وحدة حسب نوعها.

### الحقول (Fields)

| الحقل | النوع | الوصف | ملاحظات |
|-------|------|-------|---------|
| `id` | bigint | المعرف الفريد | Primary Key, Auto Increment |
| `unit_id` | bigint | معرف الوحدة | Foreign Key → `units.id`, NOT NULL |
| `key` | varchar(255) | مفتاح المواصفة | NOT NULL, مثال: 'bedrooms', 'bathrooms', 'parking' |
| `value` | text | قيمة المواصفة | NULL, يمكن أن يكون نص أو رقم |
| `type` | varchar(50) | نوع القيمة | NULL, مثال: 'number', 'text', 'boolean' |

### العلاقات (Relationships)
- **Belongs To**: `units` (unit_id) - المواصفة تنتمي لوحدة واحدة

### Indexes
- `unit_id` - للبحث السريع عن مواصفات وحدة معينة
- `key` - للبحث السريع بمفتاح معين
- UNIQUE(`unit_id`, `key`) - لضمان عدم تكرار نفس المفتاح في نفس الوحدة

### أمثلة على المواصفات
- `bedrooms` = "3" (عدد غرف النوم)
- `bathrooms` = "2" (عدد الحمامات)
- `parking` = "1" (عدد أماكن الوقوف)
- `balcony` = "true" (يوجد شرفة)
- `furnished` = "true" (مفروشة)
- `air_conditioning` = "true" (مكيف)
- `elevator` = "true" (مصعد)

### أمثلة على الاستخدام
```php
// إضافة مواصفات لوحدة
UnitSpecification::create([
    'unit_id' => 1,
    'key' => 'bedrooms',
    'value' => '3',
    'type' => 'number',
]);

UnitSpecification::create([
    'unit_id' => 1,
    'key' => 'bathrooms',
    'value' => '2',
    'type' => 'number',
]);

UnitSpecification::create([
    'unit_id' => 1,
    'key' => 'furnished',
    'value' => 'true',
    'type' => 'boolean',
]);
```

---

## الهيكل الهرمي (Hierarchical Structure)

الهيكل الكامل للملكية يتبع التسلسل التالي:

```
Ownership (الملكية)
  └── Portfolio (المحفظة)
        ├── Portfolio Location (موقع المحفظة)
        └── Building (المبنى)
              ├── Building Floor (الطابق)
              │     └── Unit (الوحدة)
              │           └── Unit Specification (مواصفات الوحدة)
              └── Unit (الوحدة - بدون طابق محدد)
                    └── Unit Specification (مواصفات الوحدة)
```

### ملاحظات على الهيكل
1. **المحفظة** يمكن أن تحتوي على محافظ فرعية (parent-child)
2. **المبنى** يمكن أن يحتوي على مباني فرعية (parent-child)
3. **الوحدة** يمكن أن تكون بدون طابق محدد (`floor_id = NULL`)
4. جميع المستويات مرتبطة مباشرة بـ `ownership_id` لتسهيل الاستعلامات

---

## الاستعلامات الشائعة (Common Queries)

### الحصول على جميع وحدات ملكية معينة
```php
$units = Unit::where('ownership_id', $ownershipId)
    ->with(['building', 'floor'])
    ->get();
```

### الحصول على جميع مباني محفظة معينة
```php
$buildings = Building::where('portfolio_id', $portfolioId)
    ->with(['floors', 'units'])
    ->get();
```

### الحصول على جميع الطوابق في مبنى معين
```php
$floors = BuildingFloor::where('building_id', $buildingId)
    ->orderBy('number')
    ->with('units')
    ->get();
```

### البحث عن وحدات متاحة في مبنى معين
```php
$availableUnits = Unit::where('building_id', $buildingId)
    ->where('status', 'available')
    ->where('active', true)
    ->get();
```

---

## أفضل الممارسات (Best Practices)

1. **الكودات (Codes)**: استخدم كودات واضحة ومنظمة (مثل: PORT-001, BLD-001, UNIT-101)
2. **الأرقام (Numbers)**: استخدم أرقام منطقية للطوابق والوحدات
3. **الحالة (Status)**: قم بتحديث حالة الوحدة عند تأجيرها أو حجزها
4. **المساحة (Area)**: احتفظ بالمساحة بالوحدة القياسية (متر مربع)
5. **الأسعار (Prices)**: احتفظ بالأسعار بالعملة المحلية (ريال سعودي)
6. **التفعيل (Active)**: استخدم `active = false` لإخفاء العناصر المحذوفة منطقياً بدلاً من حذفها فعلياً

---

## الأمان والصلاحيات (Security & Permissions)

جميع الجداول مرتبطة بـ `ownership_id`، مما يعني:
- المستخدمون يمكنهم الوصول فقط للبيانات المرتبطة بملكياتهم
- الـ middleware `ownership.scope` يضمن أن جميع الاستعلامات محدودة بملكية المستخدم الحالية
- Super Admin يمكنه الوصول لجميع البيانات

---

## الخلاصة (Summary)

هذه الجداول تشكل الهيكل الأساسي لإدارة الممتلكات في النظام:
- **Portfolios**: تجميع الممتلكات
- **Buildings**: المباني الفعلية
- **Floors**: الطوابق داخل المباني
- **Units**: الوحدات القابلة للتأجير
- **Specifications**: المواصفات الإضافية

جميع الجداول مصممة لتكون مرنة وقابلة للتوسع مع الحفاظ على الأداء والسلامة البيانات.

