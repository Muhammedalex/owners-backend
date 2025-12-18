<?php

namespace App\Services\V1\Mail;

use App\Repositories\V1\Setting\Interfaces\SystemSettingRepositoryInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class OwnershipMailService
{
    /**
     * Mail settings keys for ownership-specific configuration.
     */
    private const MAIL_SETTINGS_KEYS = [
        'mail_host' => 'smtp_host',
        'mail_port' => 'smtp_port',
        'mail_username' => 'smtp_username',
        'mail_password' => 'smtp_password',
        'mail_encryption' => 'smtp_encryption',
        'mail_from_address' => 'email_from_address',
        'mail_from_name' => 'email_from_name',
    ];

    public function __construct(
        private SystemSettingRepositoryInterface $settingRepository
    ) {}

    /**
     * Get mailer name for ownership-specific emails.
     * Creates a dynamic mailer if ownership has custom SMTP settings.
     *
     * @param int|null $ownershipId Ownership ID (null for system-wide)
     * @return string Mailer name to use
     */
    public function getMailerForOwnership(?int $ownershipId): string
    {
        // If no ownership ID, use default system mailer
        if (!$ownershipId) {
            return config('mail.default');
        }

        // Check if ownership has custom mail settings
        $mailSettings = $this->getOwnershipMailSettings($ownershipId);

        // If no custom settings, use default mailer
        if (empty($mailSettings) || !$this->hasValidSmtpSettings($mailSettings)) {
            return config('mail.default');
        }

        // Create/use dynamic mailer for this ownership
        $mailerName = "ownership_{$ownershipId}";
        $this->configureOwnershipMailer($mailerName, $mailSettings);

        return $mailerName;
    }

    /**
     * Get mail settings for a specific ownership.
     *
     * @param int $ownershipId
     * @return array
     */
    private function getOwnershipMailSettings(int $ownershipId): array
    {
        $settings = $this->settingRepository->all([
            'ownership_id' => $ownershipId,
            'group' => 'notification',
        ]);

        $mailSettings = [];
        foreach ($settings as $setting) {
            // Map setting keys to mail config keys
            if (isset(self::MAIL_SETTINGS_KEYS[$setting->key])) {
                $mailKey = self::MAIL_SETTINGS_KEYS[$setting->key];
                $mailSettings[$mailKey] = $this->getTypedValue($setting);
            } else {
                // Direct key mapping (e.g., smtp_host -> smtp_host)
                $mailSettings[$setting->key] = $this->getTypedValue($setting);
            }
        }

        return $mailSettings;
    }

    /**
     * Check if SMTP settings are valid and complete.
     *
     * @param array $settings
     * @return bool
     */
    private function hasValidSmtpSettings(array $settings): bool
    {
        // Minimum required: host, port, username, password
        $required = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password'];
        
        foreach ($required as $key) {
            if (empty($settings[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Configure a dynamic mailer for ownership.
     *
     * @param string $mailerName
     * @param array $settings
     * @return void
     */
    private function configureOwnershipMailer(string $mailerName, array $settings): void
    {
        // Get default SMTP config as base
        $defaultConfig = config('mail.mailers.smtp', []);

        // Merge ownership-specific settings
        $mailerConfig = [
            'transport' => 'smtp',
            'host' => $settings['smtp_host'] ?? $defaultConfig['host'] ?? '127.0.0.1',
            'port' => (int) ($settings['smtp_port'] ?? $defaultConfig['port'] ?? 587),
            'username' => $settings['smtp_username'] ?? $defaultConfig['username'] ?? null,
            'password' => $settings['smtp_password'] ?? $defaultConfig['password'] ?? null,
            'encryption' => $settings['smtp_encryption'] ?? $defaultConfig['encryption'] ?? 'tls',
            'timeout' => $defaultConfig['timeout'] ?? null,
            'local_domain' => $defaultConfig['local_domain'] ?? parse_url(config('app.url'), PHP_URL_HOST),
        ];

        // Dynamically add mailer to config
        Config::set("mail.mailers.{$mailerName}", $mailerConfig);

        // Set from address/name if provided
        if (isset($settings['email_from_address'])) {
            Config::set("mail.from.address", $settings['email_from_address']);
        }
        if (isset($settings['email_from_name'])) {
            Config::set("mail.from.name", $settings['email_from_name']);
        }
    }

    /**
     * Get typed value from setting based on value_type.
     *
     * @param \App\Models\V1\Setting\SystemSetting $setting
     * @return mixed
     */
    private function getTypedValue($setting)
    {
        // Return null if value is null or empty
        if ($setting->value === null || $setting->value === '') {
            return null;
        }

        return match ($setting->value_type) {
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Send email using ownership-specific mailer.
     *
     * @param int|null $ownershipId
     * @param string $to
     * @param \Illuminate\Mail\Mailable $mailable
     * @return void
     */
    public function sendForOwnership(?int $ownershipId, string $to, $mailable): void
    {
        $mailer = $this->getMailerForOwnership($ownershipId);
        Mail::mailer($mailer)->to($to)->send($mailable);
    }
}

