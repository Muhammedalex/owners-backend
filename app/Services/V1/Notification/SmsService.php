<?php

namespace App\Services\V1\Notification;

use App\Repositories\V1\Setting\Interfaces\SystemSettingRepositoryInterface;
use Illuminate\Support\Facades\Log;

// Note: Twilio SDK is required. Install via: composer require twilio/sdk
// use Twilio\Rest\Client as TwilioClient;

class SmsService
{
    // private ?TwilioClient $twilioClient = null;

    public function __construct(
        private SystemSettingRepositoryInterface $settingRepository
    ) {}

    /**
     * Send OTP via SMS.
     *
     * @param string $phone
     * @param string $otp
     * @param string $purpose
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     * @return bool
     */
    public function sendOtp(string $phone, string $otp, string $purpose, ?int $ownershipId = null): bool
    {
        // Get setting source for logging
        $settingsSource = $this->getSettingSource('sms_enabled', $ownershipId);

        // In local/testing environment, skip actual sending and just log
        if (app()->environment(['local', 'testing'])) {
            $message = $this->formatOtpMessage($otp, $purpose);
            Log::info('SMS OTP (local environment - not sent)', [
                'phone' => $phone,
                'purpose' => $purpose,
                'otp' => $otp,
                'message' => $message,
                'ownership_id' => $ownershipId,
                'settings_source' => $settingsSource,
                'environment' => app()->environment(),
                'note' => 'In local environment, OTP is always 123456. SMS is not sent.',
            ]);
            return true;
        }

        // Get setting source for logging
        $settingsSource = $this->getSettingSource('sms_enabled', $ownershipId);

        // Check if SMS is enabled (ownership-specific or system-wide)
        if (!$this->isSmsEnabled($ownershipId)) {
            Log::warning('SMS is disabled, skipping OTP send', [
                'phone' => $phone,
                'purpose' => $purpose,
                'ownership_id' => $ownershipId,
                'settings_source' => $settingsSource,
            ]);
            return false;
        }

        // Get Twilio client
        $client = $this->getTwilioClient($ownershipId);
        if (!$client) {
            Log::error('Twilio client not configured', [
                'phone' => $phone,
                'purpose' => $purpose,
                'ownership_id' => $ownershipId,
            ]);
            return false;
        }

        // Format message
        $message = $this->formatOtpMessage($otp, $purpose);

        // Get Twilio phone number
        $fromNumber = $this->getTwilioPhoneNumber($ownershipId);
        if (!$fromNumber) {
            Log::error('Twilio phone number not configured', [
                'ownership_id' => $ownershipId,
            ]);
            return false;
        }

        try {
            // Send SMS via Twilio
            $twilioResponse = $client->messages->create(
                $phone,
                [
                    'from' => $fromNumber,
                    'body' => $message,
                ]
            );

            // Get settings source for logging
            $settingsSource = $this->getSettingSource('sms_enabled', $ownershipId);

            // Log Twilio response with settings information
            Log::info('Twilio SMS sent successfully', [
                'phone' => $phone,
                'purpose' => $purpose,
                'ownership_id' => $ownershipId,
                'settings_source' => $settingsSource,
                'twilio_sid' => $twilioResponse->sid,
                'twilio_status' => $twilioResponse->status,
                'twilio_date_created' => $twilioResponse->dateCreated?->format('Y-m-d H:i:s'),
                'twilio_date_sent' => $twilioResponse->dateSent?->format('Y-m-d H:i:s'),
                'twilio_error_code' => $twilioResponse->errorCode,
                'twilio_error_message' => $twilioResponse->errorMessage,
                'from' => $fromNumber,
                'message_length' => strlen($message),
                'environment' => app()->environment(),
            ]);

            return true;
        } catch (\Exception $e) {
            // Extract Twilio error code and message if available
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            // Check for specific Twilio error codes
            $isGeoPermissionError = str_contains($errorMessage, 'Permission to send an SMS has not been enabled for the region');
            $isInvalidNumber = str_contains($errorMessage, 'Invalid') || $errorCode === 21211;
            
            Log::error('Twilio SMS failed', [
                'phone' => $phone,
                'purpose' => $purpose,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'from' => $fromNumber,
                'environment' => app()->environment(),
                'is_geo_permission_error' => $isGeoPermissionError,
                'is_invalid_number' => $isInvalidNumber,
                'suggestion' => $isGeoPermissionError 
                    ? 'Enable SMS permissions for Saudi Arabia (+966) in Twilio Console under Geo Permissions'
                    : ($isInvalidNumber ? 'Check if the phone number format is correct' : 'Check Twilio account settings and credentials'),
            ]);

            return false;
        }
    }

    /**
     * Send WhatsApp message.
     *
     * @param string $phone
     * @param string $message
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     * @return bool
     */
    public function sendWhatsApp(string $phone, string $message, ?int $ownershipId = null): bool
    {
        // Check if WhatsApp is enabled (ownership-specific or system-wide)
        if (!$this->isWhatsAppEnabled($ownershipId)) {
            Log::warning('WhatsApp is disabled, skipping message send', [
                'phone' => $phone,
            ]);
            return false;
        }

        // Get Twilio client
        $client = $this->getTwilioClient($ownershipId);
        if (!$client) {
            Log::error('Twilio client not configured for WhatsApp', [
                'phone' => $phone,
                'ownership_id' => $ownershipId,
            ]);
            return false;
        }

        // Get Twilio WhatsApp number
        $fromNumber = $this->getTwilioWhatsAppNumber($ownershipId);
        if (!$fromNumber) {
            Log::error('Twilio WhatsApp number not configured', [
                'ownership_id' => $ownershipId,
            ]);
            return false;
        }

        try {
            // Send WhatsApp message via Twilio
            $twilioResponse = $client->messages->create(
                "whatsapp:{$phone}",
                [
                    'from' => "whatsapp:{$fromNumber}",
                    'body' => $message,
                ]
            );

            // Log Twilio response
            Log::info('Twilio WhatsApp message sent successfully', [
                'phone' => $phone,
                'twilio_sid' => $twilioResponse->sid,
                'twilio_status' => $twilioResponse->status,
                'twilio_date_created' => $twilioResponse->dateCreated?->format('Y-m-d H:i:s'),
                'twilio_date_sent' => $twilioResponse->dateSent?->format('Y-m-d H:i:s'),
                'twilio_error_code' => $twilioResponse->errorCode,
                'twilio_error_message' => $twilioResponse->errorMessage,
                'from' => $fromNumber,
                'message_length' => strlen($message),
                'environment' => app()->environment(),
            ]);

            return true;
        } catch (\Exception $e) {
            // Extract Twilio error code and message if available
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            // Check for specific Twilio error codes
            $isGeoPermissionError = str_contains($errorMessage, 'Permission to send') || str_contains($errorMessage, 'region');
            $isInvalidNumber = str_contains($errorMessage, 'Invalid') || $errorCode === 21211;
            
            Log::error('Twilio WhatsApp message failed', [
                'phone' => $phone,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'from' => $fromNumber,
                'environment' => app()->environment(),
                'is_geo_permission_error' => $isGeoPermissionError,
                'is_invalid_number' => $isInvalidNumber,
                'suggestion' => $isGeoPermissionError 
                    ? 'Enable WhatsApp/SMS permissions for Saudi Arabia (+966) in Twilio Console under Geo Permissions'
                    : ($isInvalidNumber ? 'Check if the phone number format is correct' : 'Check Twilio account settings and credentials'),
            ]);

            return false;
        }
    }

    /**
     * Send general SMS message.
     *
     * @param string $phone
     * @param string $message
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     * @return bool
     */
    public function sendMessage(string $phone, string $message, ?int $ownershipId = null): bool
    {
        // Get setting source for logging
        $settingsSource = $this->getSettingSource('sms_enabled', $ownershipId);

        // In local/testing environment, skip actual sending and just log
        if (app()->environment(['local', 'testing'])) {
            Log::info('SMS Message (local environment - not sent)', [
                'phone' => $phone,
                'message' => $message,
                'ownership_id' => $ownershipId,
                'settings_source' => $settingsSource,
                'environment' => app()->environment(),
            ]);
            return true;
        }

        // Check if SMS is enabled (ownership-specific or system-wide)
        if (!$this->isSmsEnabled($ownershipId)) {
            Log::warning('SMS is disabled, skipping message send', [
                'phone' => $phone,
                'ownership_id' => $ownershipId,
                'settings_source' => $settingsSource,
            ]);
            return false;
        }

        // Get Twilio client
        $client = $this->getTwilioClient($ownershipId);
        if (!$client) {
            Log::error('Twilio client not configured', [
                'phone' => $phone,
                'ownership_id' => $ownershipId,
            ]);
            return false;
        }

        // Get Twilio phone number
        $fromNumber = $this->getTwilioPhoneNumber($ownershipId);
        if (!$fromNumber) {
            Log::error('Twilio phone number not configured', [
                'ownership_id' => $ownershipId,
            ]);
            return false;
        }

        try {
            // Send SMS via Twilio
            $twilioResponse = $client->messages->create(
                $phone,
                [
                    'from' => $fromNumber,
                    'body' => $message,
                ]
            );

            // Log Twilio response
            Log::info('Twilio SMS sent successfully', [
                'phone' => $phone,
                'ownership_id' => $ownershipId,
                'settings_source' => $settingsSource,
                'twilio_sid' => $twilioResponse->sid,
                'twilio_status' => $twilioResponse->status,
                'message_length' => strlen($message),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Twilio SMS failed', [
                'phone' => $phone,
                'ownership_id' => $ownershipId,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            return false;
        }
    }

    /**
     * Format OTP message.
     */
    private function formatOtpMessage(string $otp, string $purpose): string
    {
        $messages = [
            'login' => "Your login OTP is: {$otp}. Valid for 10 minutes. Do not share this code with anyone.",
            'forgot_password' => "Your password reset OTP is: {$otp}. Valid for 10 minutes. Do not share this code with anyone.",
            'reset_password' => "Your password reset OTP is: {$otp}. Valid for 10 minutes. Do not share this code with anyone.",
            'phone_verification' => "Your verification OTP is: {$otp}. Valid for 10 minutes. Do not share this code with anyone.",
            'email_verification' => "Your verification OTP is: {$otp}. Valid for 10 minutes. Do not share this code with anyone.",
        ];

        return $messages[$purpose] ?? "Your OTP is: {$otp}. Valid for 10 minutes. Do not share this code with anyone.";
    }

    /**
     * Get Twilio client.
     * 
     * Note: Requires twilio/sdk package. Install via: composer require twilio/sdk
     * 
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     */
    private function getTwilioClient(?int $ownershipId = null)
    {
        // Check if Twilio SDK is installed
        if (!class_exists(\Twilio\Rest\Client::class)) {
            Log::warning('Twilio SDK not installed. Install via: composer require twilio/sdk');
            return null;
        }

        // Get Twilio credentials (ownership-specific or system-wide)
        $sid = $this->getSetting('twilio_sid', null, $ownershipId);
        $token = $this->getSetting('twilio_token', null, $ownershipId);

        if (!$sid || !$token) {
            Log::warning('Twilio credentials missing', [
                'ownership_id' => $ownershipId,
                'sid_exists' => !empty($sid),
                'token_exists' => !empty($token),
            ]);
            return null;
        }

        try {
            // In local/testing environment, disable SSL verification
            if (app()->environment(['local', 'testing'])) {
                $httpClient = new \Twilio\Http\CurlClient([
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ]);
                
                return new \Twilio\Rest\Client($sid, $token, null, null, $httpClient);
            }

            // In production, use default client with SSL verification enabled
            return new \Twilio\Rest\Client($sid, $token);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Twilio client', [
                'error' => $e->getMessage(),
                'ownership_id' => $ownershipId,
                'environment' => app()->environment(),
            ]);
            return null;
        }
    }

    /**
     * Get Twilio phone number for SMS.
     * 
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     */
    private function getTwilioPhoneNumber(?int $ownershipId = null): ?string
    {
        return $this->getSetting('twilio_phone', null, $ownershipId);
    }

    /**
     * Get Twilio WhatsApp number.
     * 
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     */
    private function getTwilioWhatsAppNumber(?int $ownershipId = null): ?string
    {
        return $this->getSetting('twilio_whatsapp_phone', null, $ownershipId) 
            ?? $this->getSetting('twilio_phone', null, $ownershipId);
    }

    /**
     * Check if SMS is enabled.
     * 
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     * @return bool
     */
    public function isSmsEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->getSetting('sms_enabled', false, $ownershipId);
    }

    /**
     * Check if WhatsApp is enabled.
     * 
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     */
    private function isWhatsAppEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->getSetting('whatsapp_enabled', false, $ownershipId);
    }

    /**
     * Get setting value with fallback.
     * 
     * First tries ownership-specific setting, then falls back to system-wide setting.
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @param int|null $ownershipId Optional ownership ID for ownership-specific settings
     * @return mixed
     */
    private function getSetting(string $key, $default = null, ?int $ownershipId = null)
    {
        // Use repository's getValue method which handles fallback automatically
        return $this->settingRepository->getValue($key, $ownershipId, $default);
    }

    /**
     * Get setting source (ownership-specific or system-wide) for logging.
     * 
     * @param string $key Setting key
     * @param int|null $ownershipId Optional ownership ID
     * @return string
     */
    private function getSettingSource(string $key, ?int $ownershipId = null): string
    {
        if ($ownershipId === null) {
            return 'system-wide';
        }

        // Check if ownership-specific setting exists
        $ownershipSetting = $this->settingRepository->findByKey($key, $ownershipId);
        if ($ownershipSetting) {
            return 'ownership-specific';
        }

        // Check if system-wide setting exists
        $systemSetting = $this->settingRepository->findByKey($key, null);
        return $systemSetting ? 'system-wide (fallback)' : 'system-wide (default)';
    }
}

