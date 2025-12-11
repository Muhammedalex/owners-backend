# System Settings Plan - Ownership Scoped Settings

## Overview

System settings will be divided into two categories:
1. **Ownership Settings** - Settings specific to each ownership (scoped by `ownership_id`)
2. **System Settings** - Global settings managed by Super Admin only

---

## Database Structure

### Modified `system_settings` Table

```sql
CREATE TABLE system_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ownership_id BIGINT NULL, -- NULL = System-wide setting, NOT NULL = Ownership-specific
    `key` VARCHAR(255) NOT NULL,
    value TEXT,
    value_type VARCHAR(20) NOT NULL, -- Type of value: string, integer, decimal, boolean, json, array
    `group` VARCHAR(50) NOT NULL, -- Group for permissions: financial, contract, invoice, tenant, notification, maintenance, facility, document, media, reporting, localization, security, system
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY unique_key_ownership (`key`, ownership_id), -- Key must be unique per ownership
    INDEX idx_ownership_id (ownership_id),
    INDEX idx_group (`group`),
    INDEX idx_key (`key`)
);
```

**Key Points:**
- `ownership_id = NULL` → System-wide setting (Super Admin only)
- `ownership_id = NOT NULL` → Ownership-specific setting
- `value_type` → Type of the value (string, integer, decimal, boolean, json, array)
- `group` → Group for permission-based access control (financial, contract, invoice, etc.)
- Unique constraint on `(key, ownership_id)` allows same key for different ownerships
- System-wide settings have `ownership_id = NULL`, so they're unique by key only

---

## Ownership-Specific Settings

### 1. Financial Settings (`group: 'financial'`)

| Key | Default Value | Description | Value Type | Permission Required |
|-----|---------------|-------------|------------|---------------------|
| `tax_rate` | `15.00` | VAT/Tax rate percentage | decimal | `settings.financial.update` |
| `currency` | `SAR` | Currency code | string | `settings.financial.update` |
| `currency_symbol` | `ر.س` | Currency symbol | string | `settings.financial.update` |
| `invoice_number_prefix` | `INV` | Invoice number prefix | string | `settings.financial.update` |
| `invoice_number_format` | `{prefix}-{ownership_id}-{year}-{number}` | Invoice number format | string | `settings.financial.update` |
| `contract_number_prefix` | `CNT` | Contract number prefix | string | `settings.financial.update` |
| `contract_number_format` | `{prefix}-{ownership_id}-{year}-{number}` | Contract number format | string | `settings.financial.update` |
| `payment_terms_days` | `7` | Default days to pay after invoice due | integer | `settings.financial.update` |
| `late_payment_penalty_rate` | `0` | Late payment penalty percentage | decimal | `settings.financial.update` |
| `default_deposit_percentage` | `0` | Default deposit as percentage of rent | decimal | `settings.financial.update` |
| `auto_calculate_tax` | `true` | Auto-calculate tax on invoices | boolean | `settings.financial.update` |

### 2. Contract Settings (`type: 'contract'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `default_contract_duration_months` | `12` | Default contract duration in months | integer |
| `auto_renewal_enabled` | `false` | Enable automatic contract renewal | boolean |
| `ejar_integration_enabled` | `false` | Enable Ejar platform integration | boolean |
| `contract_approval_required` | `true` | Require approval before activating contract | boolean |
| `default_payment_frequency` | `monthly` | Default payment frequency | string |
| `contract_expiry_reminder_days` | `30` | Days before expiry to send reminder | integer |
| `allow_contract_versions` | `true` | Allow contract versioning/renewals | boolean |
| `require_digital_signature` | `false` | Require digital signature on contracts | boolean |

### 3. Invoice Settings (`type: 'invoice'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `auto_generate_invoices` | `false` | Auto-generate invoices (Saudi requirement: on-demand) | boolean |
| `invoice_due_days` | `7` | Days after period end for invoice due date | integer |
| `invoice_reminder_days` | `3` | Days before due date to send reminder | integer |
| `tax_included_in_price` | `false` | Tax included in base price | boolean |
| `invoice_notes_template` | `null` | Default notes template for invoices | text |
| `require_invoice_approval` | `false` | Require approval before sending invoice | boolean |
| `invoice_numbering_reset_yearly` | `true` | Reset invoice numbering each year | boolean |

### 4. Tenant Settings (`type: 'tenant'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `payment_tracking_enabled` | `true` | Enable tenant payment tracking | boolean |
| `tenant_rating_required` | `false` | Require rating when creating tenant | boolean |
| `id_verification_required` | `true` | Require ID verification for tenants | boolean |
| `minimum_income_requirement` | `0` | Minimum income requirement (0 = disabled) | decimal |
| `income_to_rent_ratio` | `3` | Minimum income to rent ratio (e.g., 3x rent) | decimal |
| `emergency_contact_required` | `true` | Require emergency contact information | boolean |
| `tenant_auto_activation` | `false` | Auto-activate tenant on creation | boolean |

### 5. Notification Settings (`type: 'notification'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `email_notifications_enabled` | `true` | Enable email notifications | boolean |
| `sms_notifications_enabled` | `false` | Enable SMS notifications | boolean |
| `contract_expiry_reminders` | `true` | Send contract expiry reminders | boolean |
| `invoice_overdue_reminders` | `true` | Send invoice overdue reminders | boolean |
| `payment_confirmation_notifications` | `true` | Send payment confirmation notifications | boolean |
| `contract_approval_notifications` | `true` | Send contract approval notifications | boolean |
| `invoice_sent_notifications` | `true` | Send invoice sent notifications | boolean |
| `reminder_frequency_days` | `7` | Frequency of reminders in days | integer |

### 6. Maintenance Settings (`type: 'maintenance'`) - Future

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `maintenance_auto_assignment` | `false` | Auto-assign maintenance requests | boolean |
| `default_response_time_hours` | `24` | Default response time in hours | integer |
| `maintenance_fee_structure` | `null` | Maintenance fee structure (JSON) | json |
| `urgent_maintenance_hours` | `4` | Response time for urgent requests | integer |
| `maintenance_approval_required` | `false` | Require approval for maintenance costs | boolean |

### 7. Facility Settings (`type: 'facility'`) - Future

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `booking_approval_required` | `true` | Require approval for facility bookings | boolean |
| `booking_fee_structure` | `null` | Booking fee structure (JSON) | json |
| `max_booking_duration_hours` | `24` | Maximum booking duration in hours | integer |
| `advance_booking_days` | `30` | Days in advance for booking | integer |
| `cancellation_policy_hours` | `24` | Hours before booking to cancel | integer |

### 8. Document Settings (`type: 'document'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `document_retention_days` | `2555` | Document retention period (7 years) | integer |
| `auto_archive_expired_documents` | `true` | Auto-archive expired documents | boolean |
| `required_document_types` | `[]` | Required document types (JSON array) | json |
| `max_document_size_mb` | `10` | Maximum document size in MB | integer |
| `allowed_document_types` | `['pdf','doc','docx','jpg','png']` | Allowed document file types | json |

### 9. Media Settings (`type: 'media'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `max_media_size_mb` | `5` | Maximum media file size in MB | integer |
| `allowed_media_types` | `['jpg','jpeg','png','gif','mp4','pdf']` | Allowed media file types | json |
| `auto_resize_images` | `true` | Auto-resize large images | boolean |
| `image_quality` | `85` | Image compression quality (1-100) | integer |
| `media_storage_location` | `local` | Storage location (local/s3) | string |

### 10. Reporting Settings (`type: 'reporting'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `report_cache_duration_minutes` | `5` | Report cache duration in minutes | integer |
| `auto_generate_reports` | `false` | Auto-generate reports | boolean |
| `report_delivery_method` | `email` | Report delivery method | string |
| `default_report_period_months` | `12` | Default report period in months | integer |
| `report_retention_days` | `365` | Report retention period in days | integer |

### 11. Localization Settings (`type: 'localization'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `default_language` | `ar` | Default language code | string |
| `date_format` | `Y-m-d` | Date format | string |
| `time_format` | `H:i` | Time format | string |
| `currency_display_format` | `{symbol} {amount}` | Currency display format | string |
| `number_format` | `en` | Number format (en/ar) | string |
| `timezone` | `Asia/Riyadh` | Default timezone | string |

### 12. Security Settings (`type: 'security'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `session_timeout_minutes` | `120` | Session timeout in minutes | integer |
| `max_login_attempts` | `5` | Maximum login attempts | integer |
| `password_reset_token_expiry_hours` | `24` | Password reset token expiry | integer |
| `two_factor_authentication_enabled` | `false` | Enable 2FA | boolean |
| `ip_whitelist_enabled` | `false` | Enable IP whitelist | boolean |
| `ip_whitelist` | `[]` | Allowed IP addresses (JSON array) | json |

---

## System-Wide Settings (Super Admin Only)

### 1. System Configuration (`type: 'system'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `system_name` | `Ownership Management System` | System name | string |
| `system_logo` | `null` | System logo path | string |
| `default_timezone` | `Asia/Riyadh` | Default system timezone | string |
| `default_language` | `ar` | Default system language | string |
| `maintenance_mode` | `false` | Maintenance mode enabled | boolean |
| `maintenance_message` | `System is under maintenance` | Maintenance mode message | string |
| `registration_enabled` | `true` | Allow user registration | boolean |
| `email_verification_required` | `true` | Require email verification | boolean |
| `phone_verification_required` | `false` | Require phone verification | boolean |

### 2. Email Configuration (`type: 'email'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `smtp_host` | `null` | SMTP host | string |
| `smtp_port` | `587` | SMTP port | integer |
| `smtp_username` | `null` | SMTP username | string |
| `smtp_password` | `null` | SMTP password (encrypted) | string |
| `smtp_encryption` | `tls` | SMTP encryption (tls/ssl) | string |
| `email_from_address` | `noreply@example.com` | Default from email address | string |
| `email_from_name` | `Ownership Management System` | Default from name | string |
| `email_queue_enabled` | `true` | Use queue for emails | boolean |

### 3. Security Settings (`type: 'security'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `password_min_length` | `8` | Minimum password length | integer |
| `password_require_uppercase` | `true` | Require uppercase letters | boolean |
| `password_require_lowercase` | `true` | Require lowercase letters | boolean |
| `password_require_numbers` | `true` | Require numbers | boolean |
| `password_require_symbols` | `false` | Require special characters | boolean |
| `session_timeout_minutes` | `120` | Global session timeout | integer |
| `max_login_attempts` | `5` | Maximum login attempts | integer |
| `lockout_duration_minutes` | `15` | Account lockout duration | integer |
| `two_factor_authentication_enabled` | `false` | Enable 2FA globally | boolean |
| `rate_limiting_enabled` | `true` | Enable API rate limiting | boolean |
| `rate_limit_per_minute` | `60` | API requests per minute | integer |

### 4. Feature Flags (`type: 'features'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `maintenance_module_enabled` | `false` | Enable maintenance module | boolean |
| `facility_module_enabled` | `false` | Enable facility module | boolean |
| `reports_module_enabled` | `true` | Enable reports module | boolean |
| `media_upload_enabled` | `true` | Enable media upload | boolean |
| `document_upload_enabled` | `true` | Enable document upload | boolean |
| `notifications_enabled` | `true` | Enable notifications | boolean |
| `audit_logging_enabled` | `true` | Enable audit logging | boolean |
| `api_documentation_enabled` | `true` | Enable API documentation | boolean |

### 5. Integration Settings (`type: 'integration'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `ejar_api_enabled` | `false` | Enable Ejar API integration | boolean |
| `ejar_api_key` | `null` | Ejar API key (encrypted) | string |
| `ejar_api_url` | `null` | Ejar API endpoint | string |
| `payment_gateway_enabled` | `false` | Enable payment gateway | boolean |
| `payment_gateway_provider` | `null` | Payment gateway provider | string |
| `payment_gateway_config` | `null` | Payment gateway config (JSON) | json |
| `sms_gateway_enabled` | `false` | Enable SMS gateway | boolean |
| `sms_gateway_provider` | `null` | SMS gateway provider | string |
| `sms_gateway_config` | `null` | SMS gateway config (JSON) | json |

### 6. Backup & Storage (`type: 'storage'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `backup_frequency` | `daily` | Backup frequency (daily/weekly/monthly) | string |
| `backup_retention_days` | `30` | Backup retention period | integer |
| `storage_location` | `local` | Storage location (local/s3) | string |
| `s3_bucket` | `null` | S3 bucket name | string |
| `s3_region` | `null` | S3 region | string |
| `s3_access_key` | `null` | S3 access key (encrypted) | string |
| `s3_secret_key` | `null` | S3 secret key (encrypted) | string |
| `file_upload_max_size_mb` | `10` | Maximum file upload size | integer |
| `allowed_file_types` | `['pdf','doc','docx','jpg','png','jpeg']` | Allowed file types | json |

### 7. Audit & Logging (`type: 'audit'`)

| Key | Default Value | Description | Data Type |
|-----|---------------|-------------|-----------|
| `audit_log_retention_days` | `365` | Audit log retention period | integer |
| `log_level` | `info` | Logging level (debug/info/warning/error) | string |
| `log_storage_location` | `local` | Log storage location | string |
| `log_rotation_enabled` | `true` | Enable log rotation | boolean |
| `log_rotation_days` | `7` | Log rotation period | integer |
| `audit_sensitive_fields` | `['password','token','api_key']` | Fields to exclude from audit | json |

---

## Implementation Notes

### 1. Setting Priority
- **Ownership settings override system defaults** when both exist
- If ownership setting doesn't exist, use system default
- If system default doesn't exist, use hardcoded default

### 2. Access Control
- **Ownership settings**: Only users with access to that ownership can view/edit
- **System settings**: Only Super Admin can view/edit
- Regular users cannot see system settings

### 3. Caching Strategy
- Cache settings per ownership for performance
- Clear cache when settings are updated
- Cache key: `settings_{ownership_id}_{type}` or `settings_system_{type}`

### 4. Type Safety
- Store values as JSON strings
- Cast to appropriate types when retrieving
- Support: string, integer, decimal, boolean, json, array

### 5. Validation
- Validate setting keys against allowed list
- Validate values based on key type
- Prevent deletion of critical system settings

### 6. Migration Strategy
- Create default settings for existing ownerships
- Migrate any hardcoded values to settings
- Provide seeder for initial settings

---

## API Endpoints

### Ownership Settings
- `GET /api/v1/settings` - Get all settings for current ownership
- `GET /api/v1/settings/{key}` - Get specific setting
- `GET /api/v1/settings?type={type}` - Get settings by type
- `PUT /api/v1/settings/{key}` - Update setting
- `POST /api/v1/settings` - Create new setting (if allowed)

### System Settings (Super Admin Only)
- `GET /api/v1/admin/settings` - Get all system settings
- `GET /api/v1/admin/settings/{key}` - Get specific system setting
- `PUT /api/v1/admin/settings/{key}` - Update system setting
- `POST /api/v1/admin/settings` - Create new system setting

---

## Summary

**Ownership Settings:** ~60 settings across 12 categories
**System Settings:** ~40 settings across 7 categories

**Total:** ~100 configurable settings

This provides comprehensive configuration while maintaining clear separation between ownership-specific and system-wide settings.

