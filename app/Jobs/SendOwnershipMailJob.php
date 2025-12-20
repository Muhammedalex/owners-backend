<?php

namespace App\Jobs;

use App\Repositories\V1\Setting\Interfaces\SystemSettingRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOwnershipMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $ownershipId,
        public string $to,
        public Mailable $mailable
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SystemSettingRepositoryInterface $settingRepository): void
    {
        // If no ownership ID, use default mailer
        if (!$this->ownershipId) {
            Mail::to($this->to)->send($this->mailable);
            return;
        }

        // Get SMTP settings for this ownership
        $mailSettings = $this->getOwnershipMailSettings($settingRepository, $this->ownershipId);
        
        // If no valid SMTP config, skip sending
        if (!$this->hasValidSmtpSettings($mailSettings)) {
            Log::warning('SendOwnershipMailJob: No valid SMTP config for ownership', [
                'ownership_id' => $this->ownershipId,
                'to' => $this->to,
            ]);
            return;
        }

        // Build SMTP config
        $smtpConfig = $this->buildSmtpConfig($mailSettings);
        $fromAddress = $mailSettings['email_from_address'] ?? null;
        $fromName = $mailSettings['email_from_name'] ?? null;

        // Apply config and send
        $this->sendWithConfig($smtpConfig, $fromAddress, $fromName);
    }

    /**
     * Get mail settings for ownership.
     */
    private function getOwnershipMailSettings(SystemSettingRepositoryInterface $settingRepository, int $ownershipId): array
    {
        $settings = $settingRepository->all([
            'ownership_id' => $ownershipId,
            'group' => 'notification',
        ]);

        $mailSettings = [];
        $keyMap = [
            'mail_host' => 'smtp_host',
            'mail_port' => 'smtp_port',
            'mail_username' => 'smtp_username',
            'mail_password' => 'smtp_password',
            'mail_encryption' => 'smtp_encryption',
            'mail_from_address' => 'email_from_address',
            'mail_from_name' => 'email_from_name',
        ];

        foreach ($settings as $setting) {
            $key = $keyMap[$setting->key] ?? $setting->key;
            $mailSettings[$key] = $this->getTypedValue($setting);
        }

        return $mailSettings;
    }

    /**
     * Check if SMTP settings are valid.
     */
    private function hasValidSmtpSettings(array $settings): bool
    {
        $required = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password'];
        foreach ($required as $key) {
            if (empty($settings[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get typed value from setting.
     */
    private function getTypedValue($setting)
    {
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
     * Build SMTP config from settings.
     */
    private function buildSmtpConfig(array $settings): array
    {
        $defaultConfig = config('mail.mailers.smtp', []);
        $port = (int) ($settings['smtp_port'] ?? $defaultConfig['port'] ?? 587);
        
        // Auto-correct encryption based on port
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
     * Send email with custom SMTP config.
     * Creates an isolated mailer instance to avoid race conditions when multiple ownerships send emails concurrently.
     */
    private function sendWithConfig(array $smtpConfig, ?string $fromAddress, ?string $fromName): void
    {
        // Generate unique mailer name for this job instance (prevents conflicts with parallel jobs)
        $tempMailerName = 'ownership_' . $this->ownershipId . '_' . getmypid() . '_' . uniqid();
        
        $originalFromAddress = config('mail.from.address');
        $originalFromName = config('mail.from.name');

        try {
            // Register temporary mailer with custom config (isolated per job)
            Config::set("mail.mailers.{$tempMailerName}", $smtpConfig);
            
            // Set custom from address/name if provided
            if ($fromAddress) {
                Config::set('mail.from.address', $fromAddress);
            }
            if ($fromName) {
                Config::set('mail.from.name', $fromName);
            }

            // Get mailer instance (creates new isolated instance for this ownership)
            $mailer = app('mail.manager')->mailer($tempMailerName);
            
            // Send email synchronously using the isolated mailer
            $mailer->to($this->to)->sendNow($this->mailable);

        } catch (\Exception $e) {
            Log::error('SendOwnershipMailJob: Failed to send email', [
                'ownership_id' => $this->ownershipId,
                'to' => $this->to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Clean up: remove temporary mailer config and restore original settings
            Config::set("mail.mailers.{$tempMailerName}", null);
            app('mail.manager')->forgetMailers();
            
            // Restore original from address/name
            Config::set('mail.from.address', $originalFromAddress);
            Config::set('mail.from.name', $originalFromName);
        }
    }
}

