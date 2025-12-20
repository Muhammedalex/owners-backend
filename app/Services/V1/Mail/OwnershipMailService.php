<?php

namespace App\Services\V1\Mail;

use App\Repositories\V1\Setting\Interfaces\SystemSettingRepositoryInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
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
     * If mailable implements ShouldQueue, it will be handled by SendOwnershipMailJob.
     *
     * @param int|null $ownershipId
     * @param string $to
     * @param \Illuminate\Mail\Mailable $mailable
     * @return void
     */
    public function sendForOwnership(?int $ownershipId, string $to, $mailable): void
    {
        // Check if mailable implements ShouldQueue
        $isQueued = $mailable instanceof \Illuminate\Contracts\Queue\ShouldQueue;

        if ($isQueued) {
            // Dispatch job that will fetch SMTP config in the queue
            \App\Jobs\SendOwnershipMailJob::dispatch($ownershipId, $to, $mailable);
        } else {
            // Send synchronously
            $mailSettings = $ownershipId ? $this->getOwnershipMailSettings($ownershipId) : [];
            $hasCustomSettings = !empty($mailSettings) && $this->hasValidSmtpSettings($mailSettings);

            if (!$hasCustomSettings) {
                // No custom settings - use default mailer
                Mail::to($to)->send($mailable);
                return;
            }

            // Send with custom config
            $customSmtpConfig = $this->buildSmtpConfig($mailSettings);
            $customFromAddress = $mailSettings['email_from_address'] ?? null;
            $customFromName = $mailSettings['email_from_name'] ?? null;
            $this->sendSynchronously($to, $mailable, $customSmtpConfig, $customFromAddress, $customFromName);
        }
    }

    /**
     * Build SMTP configuration from settings.
     *
     * @param array $settings
     * @return array
     */
    private function buildSmtpConfig(array $settings): array
    {
        $defaultConfig = config('mail.mailers.smtp', []);
        $port = (int) ($settings['smtp_port'] ?? $defaultConfig['port'] ?? 587);
        
        // Auto-detect encryption based on port
        $encryption = $settings['smtp_encryption'] ?? null;
        if ($port === 465 && ($encryption === 'tls' || !$encryption)) {
            $encryption = 'ssl';
        } elseif ($port === 587 && ($encryption === 'ssl' || !$encryption)) {
            $encryption = 'tls';
        } elseif (!$encryption) {
            $encryption = 'tls';
        }

        return [
            'transport' => 'smtp',
            'host' => $settings['smtp_host'] ?? $defaultConfig['host'] ?? '127.0.0.1',
            'port' => $port,
            'username' => $settings['smtp_username'] ?? $defaultConfig['username'] ?? null,
            'password' => $settings['smtp_password'] ?? $defaultConfig['password'] ?? null,
            'encryption' => $encryption,
            'timeout' => $defaultConfig['timeout'] ?? null,
            'local_domain' => $defaultConfig['local_domain'] ?? parse_url(config('app.url'), PHP_URL_HOST),
        ];
    }

    /**
     * Send email synchronously with custom config.
     *
     * @param string $to
     * @param \Illuminate\Mail\Mailable $mailable
     * @param array $customSmtpConfig
     * @param string|null $customFromAddress
     * @param string|null $customFromName
     * @return void
     */
    private function sendSynchronously(
        string $to,
        $mailable,
        array $customSmtpConfig,
        ?string $customFromAddress = null,
        ?string $customFromName = null
    ): void {
        $originalSmtpConfig = config('mail.mailers.smtp');
        $originalFromAddress = config('mail.from.address');
        $originalFromName = config('mail.from.name');

        try {
            Config::set('mail.mailers.smtp', $customSmtpConfig);
            if ($customFromAddress) {
                Config::set('mail.from.address', $customFromAddress);
            }
            if ($customFromName) {
                Config::set('mail.from.name', $customFromName);
            }

            app('mail.manager')->forgetMailers();
            Mail::mailer('smtp')->to($to)->send($mailable);
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            Config::set('mail.mailers.smtp', $originalSmtpConfig);
            Config::set('mail.from.address', $originalFromAddress);
            Config::set('mail.from.name', $originalFromName);
            app('mail.manager')->forgetMailers();
        }
    }
}

