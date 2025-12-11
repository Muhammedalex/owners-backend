# Roles, Permissions & Settings Plan

## Overview

This document defines:
1. **User Types** - Types of users in the system
2. **Roles** - Organizational roles with permission sets
3. **Permissions** - Granular permissions for all modules
4. **Settings Structure** - Ownership-scoped and system-wide settings

---

## User Types

User types are stored in `users.type` field and represent the category of user:

| Type | Description | Scope |
|------|-------------|-------|
| `super_admin` | System administrator | Global (all ownerships) |
| `owner` | Property owner | Limited to assigned ownerships |
| `manager` | Property manager | Limited to assigned ownerships |
| `accountant` | Financial manager | Limited to assigned ownerships |
| `tenant` | Tenant/renter | Limited to their own data |
| `technician` | Maintenance technician | Limited to assigned ownerships |
| `staff` | General staff member | Limited to assigned ownerships |
| `viewer` | Read-only access | Limited to assigned ownerships |

**Note:** User type is informational. Access is controlled by **permissions**, not user type.

---

## Roles

Roles are organizational tools for grouping permissions. Users can have multiple roles.

### 1. Super Admin
**User Type:** `super_admin`  
**Scope:** Global (all ownerships)  
**Permissions:** ALL permissions across all modules  
**Can:**
- Manage all ownerships
- Create and manage roles
- Assign permissions
- Access system settings
- View all data across all ownerships
- Bypass ownership scope restrictions

### 2. Owner
**User Type:** `owner`  
**Scope:** Limited to assigned ownerships  
**Permissions:** Full access to their ownership(s)  
**Can:**
- Manage their ownership(s)
- Manage properties, tenants, contracts
- Manage invoices and payments
- View reports
- Manage settings for their ownership
- Assign users to their ownership
- Manage documents and media

### 3. Manager
**User Type:** `manager`  
**Scope:** Limited to assigned ownerships  
**Permissions:** Management permissions (no financial operations)  
**Can:**
- View and manage properties
- View and manage tenants
- View and manage contracts
- View invoices and payments
- View reports
- Manage maintenance requests
- Manage facility bookings
- Upload documents and media
- **Cannot:** Delete critical data, manage settings, approve contracts

### 4. Accountant
**User Type:** `accountant`  
**Scope:** Limited to assigned ownerships  
**Permissions:** Financial operations only  
**Can:**
- View all financial data
- Create and manage invoices
- Record and manage payments
- View financial reports
- View contracts (read-only)
- View tenants (read-only)
- **Cannot:** Manage properties, approve contracts, manage settings

### 5. Tenant
**User Type:** `tenant`  
**Scope:** Limited to their own data  
**Permissions:** Read-only access to their own data  
**Can:**
- View own profile
- View own contracts
- View own invoices
- View own payments
- View own maintenance requests
- Book facilities
- **Cannot:** View other tenants, manage properties, access reports

### 6. Technician
**User Type:** `technician`  
**Scope:** Limited to assigned ownerships  
**Permissions:** Maintenance operations only  
**Can:**
- View maintenance requests
- Update maintenance request status
- Add maintenance notes
- View assigned units
- **Cannot:** Create contracts, manage invoices, access financial data

### 7. Staff
**User Type:** `staff`  
**Scope:** Limited to assigned ownerships  
**Permissions:** Basic operational permissions  
**Can:**
- View properties, tenants, contracts
- Create maintenance requests
- Upload documents and media
- View basic reports
- **Cannot:** Delete data, manage settings, approve contracts, manage payments

### 8. Viewer
**User Type:** `viewer`  
**Scope:** Limited to assigned ownerships  
**Permissions:** Read-only access  
**Can:**
- View all data (read-only)
- View reports
- **Cannot:** Create, update, or delete anything

---

## Permissions Structure

### Permission Naming Convention
```
{module}.{resource}.{action}
```

**Examples:**
- `tenants.view` - View tenants
- `contracts.approve` - Approve contracts
- `settings.financial.update` - Update financial settings

---

## Complete Permissions List

### Auth Module
```
auth.users.view
auth.users.create
auth.users.update
auth.users.delete
auth.users.activate
auth.users.deactivate
auth.users.view.own
auth.users.update.own
auth.roles.view
auth.roles.create
auth.roles.update
auth.roles.delete
auth.roles.assign
auth.permissions.view
auth.permissions.assign
```

### Ownership Module
```
ownerships.view
ownerships.create
ownerships.update
ownerships.delete
ownerships.activate
ownerships.deactivate
ownerships.switch
ownerships.board.view
ownerships.board.manage
ownerships.users.view
ownerships.users.assign
ownerships.users.remove
ownerships.users.set-default
```

### Property Management Module
```
properties.portfolios.view
properties.portfolios.create
properties.portfolios.update
properties.portfolios.delete
properties.buildings.view
properties.buildings.create
properties.buildings.update
properties.buildings.delete
properties.units.view
properties.units.create
properties.units.update
properties.units.delete
```

### Tenant Module
```
tenants.view
tenants.create
tenants.update
tenants.delete
tenants.verify
tenants.rating.update
```

### Contract Module
```
contracts.view
contracts.create
contracts.update
contracts.delete
contracts.approve
contracts.sign
contracts.terminate
contracts.terms.view
contracts.terms.create
contracts.terms.update
contracts.terms.delete
```

### Invoice Module
```
invoices.view
invoices.create
invoices.update
invoices.delete
invoices.send
invoices.mark-paid
invoices.items.view
invoices.items.create
invoices.items.update
invoices.items.delete
```

### Payment Module
```
payments.view
payments.create
payments.update
payments.delete
payments.confirm
payments.mark-paid
payments.mark-unpaid
```

### Media Module (NEW)
```
media.view
media.create
media.update
media.delete
media.upload
media.download
media.reorder
```

### Documents Module (NEW)
```
documents.view
documents.create
documents.update
documents.delete
documents.upload
documents.download
documents.archive
```

### Settings Module (NEW)
```
settings.view
settings.create
settings.update
settings.delete
settings.financial.view
settings.financial.update
settings.contract.view
settings.contract.update
settings.invoice.view
settings.invoice.update
settings.tenant.view
settings.tenant.update
settings.notification.view
settings.notification.update
settings.localization.view
settings.localization.update
settings.security.view
settings.security.update
settings.system.view (Super Admin only)
settings.system.update (Super Admin only)
```

### Reports Module
```
reports.view
reports.export
reports.dashboard
reports.tenants
reports.contracts
reports.invoices
reports.payments
reports.revenue
```

### Maintenance Module (Future)
```
maintenance.categories.view
maintenance.categories.manage
maintenance.requests.view
maintenance.requests.create
maintenance.requests.update
maintenance.requests.assign
maintenance.requests.complete
maintenance.technicians.view
maintenance.technicians.manage
```

### Facility Module (Future)
```
facilities.view
facilities.create
facilities.update
facilities.delete
facilities.bookings.view
facilities.bookings.create
facilities.bookings.approve
facilities.bookings.cancel
```

### System Module
```
system.notifications.view
system.notifications.send
system.audit.view
system.logs.view
```

---

## Role-Permission Assignments

### Super Admin
- **ALL permissions** across all modules

### Owner
- All ownership permissions
- All property permissions
- All tenant permissions
- All contract permissions
- All invoice permissions
- All payment permissions
- All media permissions
- All document permissions
- All settings permissions (ownership-scoped)
- All reports permissions
- Limited system permissions (notifications, documents)

### Manager
- Ownership: view
- All property permissions
- All tenant permissions
- Contracts: view, create, update (no approve/delete)
- Invoices: view, create, update (no delete)
- Payments: view, create, update (no delete)
- Media: all permissions
- Documents: all permissions
- Settings: view only
- Reports: view
- Maintenance: all permissions
- Facilities: all permissions

### Accountant
- Ownership: view
- Properties: view
- Tenants: view
- Contracts: view
- All invoice permissions
- All payment permissions
- Documents: view, upload
- Settings: financial view, financial update
- Reports: all permissions

### Tenant
- Own profile: view, update
- Own contracts: view
- Own invoices: view
- Own payments: view
- Own maintenance requests: view, create
- Facilities: bookings view, bookings create
- Documents: own documents view, upload

### Technician
- Properties: view
- Units: view
- Maintenance requests: view, update, complete
- Documents: view, upload (related to maintenance)

### Staff
- Properties: view
- Tenants: view
- Contracts: view
- Invoices: view
- Payments: view
- Media: view, upload
- Documents: view, upload
- Maintenance requests: view, create
- Reports: view (limited)

### Viewer
- All view permissions (read-only)
- Reports: view

---

## Settings Structure (Updated)

### Database Structure

```sql
CREATE TABLE system_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ownership_id BIGINT NULL, -- NULL = System-wide, NOT NULL = Ownership-specific
    `key` VARCHAR(255) NOT NULL,
    value TEXT,
    value_type VARCHAR(20) NOT NULL, -- Type of value: string, integer, decimal, boolean, json, array
    `group` VARCHAR(50) NOT NULL, -- Group for permissions: financial, contract, invoice, tenant, notification, maintenance, facility, document, media, reporting, localization, security, system
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY unique_key_ownership (`key`, ownership_id),
    INDEX idx_ownership_id (ownership_id),
    INDEX idx_group (`group`),
    INDEX idx_key (`key`)
);
```

### Value Types
- `string` - Text value
- `integer` - Whole number
- `decimal` - Decimal number
- `boolean` - true/false
- `json` - JSON object
- `array` - JSON array

### Setting Groups (for Permissions)

1. **financial** - Financial settings (tax, currency, numbering)
2. **contract** - Contract settings (duration, approval, Ejar)
3. **invoice** - Invoice settings (due days, reminders, tax)
4. **tenant** - Tenant settings (tracking, verification, requirements)
5. **notification** - Notification settings (email, SMS, reminders)
6. **maintenance** - Maintenance settings (response times, fees)
7. **facility** - Facility settings (booking, fees, duration)
8. **document** - Document settings (retention, types, size)
9. **media** - Media settings (size, types, storage)
10. **reporting** - Reporting settings (cache, delivery, period)
11. **localization** - Localization settings (language, format, timezone)
12. **security** - Security settings (session, login, 2FA)
13. **system** - System settings (Super Admin only)

### Permission Structure for Settings

```
settings.view - View all settings
settings.create - Create new settings
settings.update - Update settings
settings.delete - Delete settings

settings.{group}.view - View settings in specific group
settings.{group}.update - Update settings in specific group

settings.system.view - View system settings (Super Admin only)
settings.system.update - Update system settings (Super Admin only)
```

**Examples:**
- `settings.financial.view` - View financial settings
- `settings.financial.update` - Update financial settings
- `settings.contract.view` - View contract settings
- `settings.contract.update` - Update contract settings

---

## Settings by Group

### 1. Financial Settings (`group: 'financial'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `tax_rate` | `15.00` | decimal | `settings.financial.update` |
| `currency` | `SAR` | string | `settings.financial.update` |
| `currency_symbol` | `ر.س` | string | `settings.financial.update` |
| `invoice_number_prefix` | `INV` | string | `settings.financial.update` |
| `contract_number_prefix` | `CNT` | string | `settings.financial.update` |
| `payment_terms_days` | `7` | integer | `settings.financial.update` |
| `late_payment_penalty_rate` | `0` | decimal | `settings.financial.update` |
| `default_deposit_percentage` | `0` | decimal | `settings.financial.update` |
| `auto_calculate_tax` | `true` | boolean | `settings.financial.update` |

### 2. Contract Settings (`group: 'contract'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `default_contract_duration_months` | `12` | integer | `settings.contract.update` |
| `auto_renewal_enabled` | `false` | boolean | `settings.contract.update` |
| `ejar_integration_enabled` | `false` | boolean | `settings.contract.update` |
| `contract_approval_required` | `true` | boolean | `settings.contract.update` |
| `default_payment_frequency` | `monthly` | string | `settings.contract.update` |
| `contract_expiry_reminder_days` | `30` | integer | `settings.contract.update` |
| `allow_contract_versions` | `true` | boolean | `settings.contract.update` |
| `require_digital_signature` | `false` | boolean | `settings.contract.update` |

### 3. Invoice Settings (`group: 'invoice'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `auto_generate_invoices` | `false` | boolean | `settings.invoice.update` |
| `invoice_due_days` | `7` | integer | `settings.invoice.update` |
| `invoice_reminder_days` | `3` | integer | `settings.invoice.update` |
| `tax_included_in_price` | `false` | boolean | `settings.invoice.update` |
| `invoice_notes_template` | `null` | string | `settings.invoice.update` |
| `require_invoice_approval` | `false` | boolean | `settings.invoice.update` |
| `invoice_numbering_reset_yearly` | `true` | boolean | `settings.invoice.update` |

### 4. Tenant Settings (`group: 'tenant'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `payment_tracking_enabled` | `true` | boolean | `settings.tenant.update` |
| `tenant_rating_required` | `false` | boolean | `settings.tenant.update` |
| `id_verification_required` | `true` | boolean | `settings.tenant.update` |
| `minimum_income_requirement` | `0` | decimal | `settings.tenant.update` |
| `income_to_rent_ratio` | `3` | decimal | `settings.tenant.update` |
| `emergency_contact_required` | `true` | boolean | `settings.tenant.update` |
| `tenant_auto_activation` | `false` | boolean | `settings.tenant.update` |

### 5. Notification Settings (`group: 'notification'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `email_notifications_enabled` | `true` | boolean | `settings.notification.update` |
| `sms_notifications_enabled` | `false` | boolean | `settings.notification.update` |
| `contract_expiry_reminders` | `true` | boolean | `settings.notification.update` |
| `invoice_overdue_reminders` | `true` | boolean | `settings.notification.update` |
| `payment_confirmation_notifications` | `true` | boolean | `settings.notification.update` |
| `contract_approval_notifications` | `true` | boolean | `settings.notification.update` |
| `invoice_sent_notifications` | `true` | boolean | `settings.notification.update` |
| `reminder_frequency_days` | `7` | integer | `settings.notification.update` |

### 6. Document Settings (`group: 'document'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `document_retention_days` | `2555` | integer | `settings.document.update` |
| `auto_archive_expired_documents` | `true` | boolean | `settings.document.update` |
| `required_document_types` | `[]` | array | `settings.document.update` |
| `max_document_size_mb` | `10` | integer | `settings.document.update` |
| `allowed_document_types` | `['pdf','doc','docx','jpg','png']` | array | `settings.document.update` |

### 7. Media Settings (`group: 'media'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `max_media_size_mb` | `5` | integer | `settings.media.update` |
| `allowed_media_types` | `['jpg','jpeg','png','gif','mp4','pdf']` | array | `settings.media.update` |
| `auto_resize_images` | `true` | boolean | `settings.media.update` |
| `image_quality` | `85` | integer | `settings.media.update` |
| `media_storage_location` | `local` | string | `settings.media.update` |

### 8. Reporting Settings (`group: 'reporting'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `report_cache_duration_minutes` | `5` | integer | `settings.reporting.update` |
| `auto_generate_reports` | `false` | boolean | `settings.reporting.update` |
| `report_delivery_method` | `email` | string | `settings.reporting.update` |
| `default_report_period_months` | `12` | integer | `settings.reporting.update` |
| `report_retention_days` | `365` | integer | `settings.reporting.update` |

### 9. Localization Settings (`group: 'localization'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `default_language` | `ar` | string | `settings.localization.update` |
| `date_format` | `Y-m-d` | string | `settings.localization.update` |
| `time_format` | `H:i` | string | `settings.localization.update` |
| `currency_display_format` | `{symbol} {amount}` | string | `settings.localization.update` |
| `number_format` | `en` | string | `settings.localization.update` |
| `timezone` | `Asia/Riyadh` | string | `settings.localization.update` |

### 10. Security Settings (`group: 'security'`)

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `session_timeout_minutes` | `120` | integer | `settings.security.update` |
| `max_login_attempts` | `5` | integer | `settings.security.update` |
| `password_reset_token_expiry_hours` | `24` | integer | `settings.security.update` |
| `two_factor_authentication_enabled` | `false` | boolean | `settings.security.update` |
| `ip_whitelist_enabled` | `false` | boolean | `settings.security.update` |
| `ip_whitelist` | `[]` | array | `settings.security.update` |

### 11. System Settings (`group: 'system'`) - Super Admin Only

| Key | Default | Value Type | Permission Required |
|-----|---------|------------|---------------------|
| `system_name` | `Ownership Management System` | string | `settings.system.update` |
| `system_logo` | `null` | string | `settings.system.update` |
| `default_timezone` | `Asia/Riyadh` | string | `settings.system.update` |
| `default_language` | `ar` | string | `settings.system.update` |
| `maintenance_mode` | `false` | boolean | `settings.system.update` |
| `maintenance_message` | `System is under maintenance` | string | `settings.system.update` |
| `registration_enabled` | `true` | boolean | `settings.system.update` |
| `email_verification_required` | `true` | boolean | `settings.system.update` |
| `phone_verification_required` | `false` | boolean | `settings.system.update` |
| `smtp_host` | `null` | string | `settings.system.update` |
| `smtp_port` | `587` | integer | `settings.system.update` |
| `smtp_username` | `null` | string | `settings.system.update` |
| `smtp_password` | `null` | string | `settings.system.update` |
| `smtp_encryption` | `tls` | string | `settings.system.update` |
| `email_from_address` | `noreply@example.com` | string | `settings.system.update` |
| `email_from_name` | `Ownership Management System` | string | `settings.system.update` |
| `password_min_length` | `8` | integer | `settings.system.update` |
| `password_require_uppercase` | `true` | boolean | `settings.system.update` |
| `password_require_lowercase` | `true` | boolean | `settings.system.update` |
| `password_require_numbers` | `true` | boolean | `settings.system.update` |
| `password_require_symbols` | `false` | boolean | `settings.system.update` |
| `rate_limiting_enabled` | `true` | boolean | `settings.system.update` |
| `rate_limit_per_minute` | `60` | integer | `settings.system.update` |
| `maintenance_module_enabled` | `false` | boolean | `settings.system.update` |
| `facility_module_enabled` | `false` | boolean | `settings.system.update` |
| `reports_module_enabled` | `true` | boolean | `settings.system.update` |
| `media_upload_enabled` | `true` | boolean | `settings.system.update` |
| `document_upload_enabled` | `true` | boolean | `settings.system.update` |
| `backup_frequency` | `daily` | string | `settings.system.update` |
| `backup_retention_days` | `30` | integer | `settings.system.update` |
| `storage_location` | `local` | string | `settings.system.update` |
| `file_upload_max_size_mb` | `10` | integer | `settings.system.update` |
| `audit_log_retention_days` | `365` | integer | `settings.system.update` |
| `log_level` | `info` | string | `settings.system.update` |

---

## Summary

### User Types: 8 types
- super_admin, owner, manager, accountant, tenant, technician, staff, viewer

### Roles: 8 roles
- Super Admin, Owner, Manager, Accountant, Tenant, Technician, Staff, Viewer

### Permissions: ~120 permissions
- Auth: 15 permissions
- Ownership: 13 permissions
- Properties: 12 permissions
- Tenants: 6 permissions
- Contracts: 9 permissions
- Invoices: 8 permissions
- Payments: 7 permissions
- Media: 7 permissions (NEW)
- Documents: 7 permissions (NEW)
- Settings: 25+ permissions (NEW)
- Reports: 7 permissions
- Maintenance: 8 permissions (Future)
- Facilities: 8 permissions (Future)
- System: 4 permissions

### Settings Groups: 13 groups
- financial, contract, invoice, tenant, notification, maintenance, facility, document, media, reporting, localization, security, system

### Settings: ~100 settings
- Ownership-scoped: ~60 settings
- System-wide: ~40 settings

---

## Implementation Priority

1. **Update Settings Structure** - Add `value_type` and `group` fields
2. **Add New Permissions** - Media, Documents, Settings permissions
3. **Update RoleSeeder** - Add new roles and assign permissions
4. **Update PermissionSeeder** - Add all new permissions
5. **Create Settings Seeder** - Seed default settings for all groups

