<?php

namespace Database\Seeders\V1\Setting;

use App\Models\V1\Ownership\Ownership;
use App\Models\V1\Setting\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Seed System-wide settings (Super Admin only)
        $this->seedSystemWideSettings();

        // Step 2: Seed Ownership-specific default settings
        $this->seedOwnershipDefaultSettings();
    }

    /**
     * Seed system-wide settings.
     */
    private function seedSystemWideSettings(): void
    {
        $this->command->info('Seeding system-wide settings...');

        // System-wide settings (Super Admin only)
        $systemSettings = [
            // System Settings
            [
                'key' => 'system_name',
                'value' => 'Ownership Management System',
                'value_type' => 'string',
                'group' => 'system',
                'description' => 'System name',
            ],
            [
                'key' => 'system_logo',
                'value' => null,
                'value_type' => 'string',
                'group' => 'system',
                'description' => 'System logo URL',
            ],
            [
                'key' => 'default_timezone',
                'value' => 'Asia/Riyadh',
                'value_type' => 'string',
                'group' => 'system',
                'description' => 'Default timezone',
            ],
            [
                'key' => 'default_language',
                'value' => 'ar',
                'value_type' => 'string',
                'group' => 'system',
                'description' => 'Default language',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'system',
                'description' => 'Maintenance mode enabled',
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'System is under maintenance',
                'value_type' => 'string',
                'group' => 'system',
                'description' => 'Maintenance mode message',
            ],
            [
                'key' => 'registration_enabled',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'system',
                'description' => 'User registration enabled',
            ],
            [
                'key' => 'email_verification_required',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'system',
                'description' => 'Email verification required',
            ],
            [
                'key' => 'phone_verification_required',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'system',
                'description' => 'Phone verification required',
            ],
        ];

        foreach ($systemSettings as $setting) {
            SystemSetting::firstOrCreate(
                [
                    'key' => $setting['key'],
                    'ownership_id' => null, // System-wide
                ],
                $setting
            );
        }

        $this->command->info('✅ System-wide settings seeded successfully.');
    }

    /**
     * Seed default settings for a specific ownership.
     * This method can be called from OwnershipService when creating a new ownership.
     */
    public function seedForOwnership(Ownership $ownership): void
    {
        $ownershipSettings = $this->getOwnershipDefaultSettings();

        foreach ($ownershipSettings as $setting) {
            SystemSetting::firstOrCreate(
                [
                    'key' => $setting['key'],
                    'ownership_id' => $ownership->id,
                ],
                array_merge($setting, ['ownership_id' => $ownership->id])
            );
        }
    }

    /**
     * Seed ownership-specific default settings.
     */
    private function seedOwnershipDefaultSettings(): void
    {
        $ownerships = Ownership::all();

        if ($ownerships->isEmpty()) {
            $this->command->warn('No ownerships found. Skipping ownership-specific settings.');
            return;
        }

        $this->command->info("Seeding default settings for {$ownerships->count()} ownership(s)...");

        $ownershipSettings = $this->getOwnershipDefaultSettings();

        $created = 0;
        $skipped = 0;

        foreach ($ownerships as $ownership) {
            foreach ($ownershipSettings as $setting) {
                $exists = SystemSetting::where('key', $setting['key'])
                    ->where('ownership_id', $ownership->id)
                    ->exists();

                if (!$exists) {
                    SystemSetting::create([
                        'ownership_id' => $ownership->id,
                        'key' => $setting['key'],
                        'value' => $setting['value'],
                        'value_type' => $setting['value_type'],
                        'group' => $setting['group'],
                        'description' => $setting['description'],
                    ]);
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->command->info("✅ Ownership-specific settings seeded successfully.");
        $this->command->info("   Created: {$created} settings");
        if ($skipped > 0) {
            $this->command->info("   Skipped: {$skipped} settings (already exist)");
        }
    }

    /**
     * Get ownership default settings array.
     * This method is extracted to be reusable.
     */
    private function getOwnershipDefaultSettings(): array
    {
        return [
            // Financial Settings
            [
                'key' => 'tax_rate',
                'value' => '15.00',
                'value_type' => 'decimal',
                'group' => 'financial',
                'description' => 'VAT/Tax rate percentage',
            ],
            [
                'key' => 'currency',
                'value' => 'SAR',
                'value_type' => 'string',
                'group' => 'financial',
                'description' => 'Currency code',
            ],
            [
                'key' => 'currency_symbol',
                'value' => 'ر.س',
                'value_type' => 'string',
                'group' => 'financial',
                'description' => 'Currency symbol',
            ],
            [
                'key' => 'invoice_number_prefix',
                'value' => 'INV',
                'value_type' => 'string',
                'group' => 'financial',
                'description' => 'Invoice number prefix',
            ],
            [
                'key' => 'contract_number_prefix',
                'value' => 'CNT',
                'value_type' => 'string',
                'group' => 'financial',
                'description' => 'Contract number prefix',
            ],
            [
                'key' => 'payment_terms_days',
                'value' => '7',
                'value_type' => 'integer',
                'group' => 'financial',
                'description' => 'Default days to pay after invoice due',
            ],
            [
                'key' => 'late_payment_penalty_rate',
                'value' => '0',
                'value_type' => 'decimal',
                'group' => 'financial',
                'description' => 'Late payment penalty percentage',
            ],
            [
                'key' => 'default_deposit_percentage',
                'value' => '0',
                'value_type' => 'decimal',
                'group' => 'financial',
                'description' => 'Default deposit as percentage of rent',
            ],
            [
                'key' => 'auto_calculate_tax',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'financial',
                'description' => 'Auto-calculate tax on invoices',
            ],

            // Contract Settings
            [
                'key' => 'default_contract_duration_months',
                'value' => '12',
                'value_type' => 'integer',
                'group' => 'contract',
                'description' => 'Default contract duration in months',
            ],
            [
                'key' => 'auto_renewal_enabled',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'contract',
                'description' => 'Enable automatic contract renewal',
            ],
            [
                'key' => 'ejar_integration_enabled',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'contract',
                'description' => 'Enable Ejar platform integration',
            ],
            [
                'key' => 'contract_approval_required',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'contract',
                'description' => 'Require approval before activating contract',
            ],
            [
                'key' => 'default_payment_frequency',
                'value' => 'monthly',
                'value_type' => 'string',
                'group' => 'contract',
                'description' => 'Default payment frequency',
            ],
            [
                'key' => 'contract_expiry_reminder_days',
                'value' => '30',
                'value_type' => 'integer',
                'group' => 'contract',
                'description' => 'Days before expiry to send reminder',
            ],
            [
                'key' => 'allow_contract_versions',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'contract',
                'description' => 'Allow contract versioning/renewals',
            ],
            [
                'key' => 'require_digital_signature',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'contract',
                'description' => 'Require digital signature on contracts',
            ],

            // Invoice Settings
            [
                'key' => 'auto_generate_invoices',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'invoice',
                'description' => 'Auto-generate invoices (Saudi requirement: on-demand)',
            ],
            [
                'key' => 'invoice_due_days',
                'value' => '7',
                'value_type' => 'integer',
                'group' => 'invoice',
                'description' => 'Days after period end for invoice due date',
            ],
            [
                'key' => 'invoice_reminder_days',
                'value' => '3',
                'value_type' => 'integer',
                'group' => 'invoice',
                'description' => 'Days before due date to send reminder',
            ],
            [
                'key' => 'tax_included_in_price',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'invoice',
                'description' => 'Tax included in base price',
            ],
            [
                'key' => 'invoice_notes_template',
                'value' => null,
                'value_type' => 'string',
                'group' => 'invoice',
                'description' => 'Default notes template for invoices',
            ],
            [
                'key' => 'require_invoice_approval',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'invoice',
                'description' => 'Require approval before sending invoice',
            ],
            [
                'key' => 'invoice_numbering_reset_yearly',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'invoice',
                'description' => 'Reset invoice numbering each year',
            ],

            // Tenant Settings
            [
                'key' => 'payment_tracking_enabled',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'tenant',
                'description' => 'Enable tenant payment tracking',
            ],
            [
                'key' => 'tenant_rating_required',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'tenant',
                'description' => 'Require rating when creating tenant',
            ],
            [
                'key' => 'id_verification_required',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'tenant',
                'description' => 'Require ID verification for tenants',
            ],
            [
                'key' => 'minimum_income_requirement',
                'value' => '0',
                'value_type' => 'decimal',
                'group' => 'tenant',
                'description' => 'Minimum income requirement (0 = disabled)',
            ],
            [
                'key' => 'income_to_rent_ratio',
                'value' => '3',
                'value_type' => 'decimal',
                'group' => 'tenant',
                'description' => 'Minimum income to rent ratio (e.g., 3x rent)',
            ],
            [
                'key' => 'emergency_contact_required',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'tenant',
                'description' => 'Require emergency contact information',
            ],
            [
                'key' => 'tenant_auto_activation',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'tenant',
                'description' => 'Auto-activate tenant on creation',
            ],

            // Notification Settings
            [
                'key' => 'email_notifications_enabled',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'notification',
                'description' => 'Enable email notifications',
            ],
            [
                'key' => 'sms_notifications_enabled',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'notification',
                'description' => 'Enable SMS notifications',
            ],
            [
                'key' => 'contract_expiry_reminders',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send contract expiry reminders',
            ],
            [
                'key' => 'invoice_overdue_reminders',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send invoice overdue reminders',
            ],
            [
                'key' => 'payment_confirmation_notifications',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send payment confirmation notifications',
            ],
            [
                'key' => 'contract_approval_notifications',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send contract approval notifications',
            ],
            [
                'key' => 'invoice_sent_notifications',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send invoice sent notifications',
            ],
            [
                'key' => 'reminder_frequency_days',
                'value' => '7',
                'value_type' => 'integer',
                'group' => 'notification',
                'description' => 'Frequency of reminders in days',
            ],

            // Document Settings
            [
                'key' => 'document_retention_days',
                'value' => '2555',
                'value_type' => 'integer',
                'group' => 'document',
                'description' => 'Document retention period (7 years)',
            ],
            [
                'key' => 'auto_archive_expired_documents',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'document',
                'description' => 'Auto-archive expired documents',
            ],
            [
                'key' => 'required_document_types',
                'value' => '[]',
                'value_type' => 'array',
                'group' => 'document',
                'description' => 'Required document types (JSON array)',
            ],
            [
                'key' => 'max_document_size_mb',
                'value' => '10',
                'value_type' => 'integer',
                'group' => 'document',
                'description' => 'Maximum document size in MB',
            ],
            [
                'key' => 'allowed_document_types',
                'value' => '["pdf","doc","docx","xls","xlsx","jpg","png"]',
                'value_type' => 'array',
                'group' => 'document',
                'description' => 'Allowed document file types (JSON array)',
            ],

            // Media Settings
            [
                'key' => 'max_media_size_mb',
                'value' => '5',
                'value_type' => 'integer',
                'group' => 'media',
                'description' => 'Maximum media file size in MB',
            ],
            [
                'key' => 'allowed_media_types',
                'value' => '["jpg","jpeg","png","gif","mp4","pdf"]',
                'value_type' => 'array',
                'group' => 'media',
                'description' => 'Allowed media file types (JSON array)',
            ],
            [
                'key' => 'auto_resize_images',
                'value' => '1',
                'value_type' => 'boolean',
                'group' => 'media',
                'description' => 'Auto-resize and optimize images',
            ],
            [
                'key' => 'image_quality',
                'value' => '85',
                'value_type' => 'integer',
                'group' => 'media',
                'description' => 'Image quality (1-100)',
            ],
            [
                'key' => 'media_storage_location',
                'value' => 'local',
                'value_type' => 'string',
                'group' => 'media',
                'description' => 'Media storage location (local, s3, etc.)',
            ],

            // Reporting Settings
            [
                'key' => 'report_cache_duration_minutes',
                'value' => '5',
                'value_type' => 'integer',
                'group' => 'reporting',
                'description' => 'Report cache duration in minutes',
            ],
            [
                'key' => 'auto_generate_reports',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'reporting',
                'description' => 'Auto-generate reports',
            ],
            [
                'key' => 'report_delivery_method',
                'value' => 'email',
                'value_type' => 'string',
                'group' => 'reporting',
                'description' => 'Report delivery method',
            ],
            [
                'key' => 'default_report_period_months',
                'value' => '12',
                'value_type' => 'integer',
                'group' => 'reporting',
                'description' => 'Default report period in months',
            ],
            [
                'key' => 'report_retention_days',
                'value' => '365',
                'value_type' => 'integer',
                'group' => 'reporting',
                'description' => 'Report retention period in days',
            ],

            // Localization Settings
            [
                'key' => 'default_language',
                'value' => 'ar',
                'value_type' => 'string',
                'group' => 'localization',
                'description' => 'Default language',
            ],
            [
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'value_type' => 'string',
                'group' => 'localization',
                'description' => 'Date format',
            ],
            [
                'key' => 'time_format',
                'value' => 'H:i',
                'value_type' => 'string',
                'group' => 'localization',
                'description' => 'Time format',
            ],
            [
                'key' => 'currency_display_format',
                'value' => '{symbol} {amount}',
                'value_type' => 'string',
                'group' => 'localization',
                'description' => 'Currency display format',
            ],
            [
                'key' => 'number_format',
                'value' => 'en',
                'value_type' => 'string',
                'group' => 'localization',
                'description' => 'Number format locale',
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Riyadh',
                'value_type' => 'string',
                'group' => 'localization',
                'description' => 'Timezone',
            ],

            // Security Settings
            [
                'key' => 'session_timeout_minutes',
                'value' => '120',
                'value_type' => 'integer',
                'group' => 'security',
                'description' => 'Session timeout in minutes',
            ],
            [
                'key' => 'max_login_attempts',
                'value' => '5',
                'value_type' => 'integer',
                'group' => 'security',
                'description' => 'Maximum login attempts',
            ],
            [
                'key' => 'password_reset_token_expiry_hours',
                'value' => '24',
                'value_type' => 'integer',
                'group' => 'security',
                'description' => 'Password reset token expiry in hours',
            ],
            [
                'key' => 'two_factor_authentication_enabled',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'security',
                'description' => 'Enable two-factor authentication',
            ],
            [
                'key' => 'ip_whitelist_enabled',
                'value' => '0',
                'value_type' => 'boolean',
                'group' => 'security',
                'description' => 'Enable IP whitelist',
            ],
            [
                'key' => 'ip_whitelist',
                'value' => '[]',
                'value_type' => 'array',
                'group' => 'security',
                'description' => 'IP whitelist (JSON array)',
            ],
        ];
    }
}
