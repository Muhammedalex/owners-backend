<?php

namespace App\Services\V1\Auth;

use App\Models\V1\Auth\OtpVerification;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use App\Services\V1\Notification\SmsService;
use App\Services\V1\Mail\OwnershipMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(
        private SmsService $smsService,
        private OwnershipMailService $mailService,
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Generate and send OTP via SMS or Email.
     *
     * @param string|null $phone
     * @param string|null $email
     * @param string $purpose
     * @param int|null $ownershipId Optional ownership ID for ownership-specific SMS/WhatsApp settings
     *                                If not provided, will be automatically determined from user's default ownership
     * @return array
     * @throws ValidationException
     */
    public function generateAndSend(?string $phone = null, ?string $email = null, string $purpose = 'login', ?int $ownershipId = null): array
    {
        // Validate that at least one identifier is provided
        if (!$phone && !$email) {
            throw ValidationException::withMessages([
                'identifier' => ['Either phone or email must be provided.'],
            ]);
        }

        // Normalize phone if provided
        $normalizedPhone = $phone ? $this->normalizePhone($phone) : null;

        // If ownershipId is not provided, try to get it from user's default ownership
        if ($ownershipId === null) {
            $ownershipId = $this->getOwnershipIdFromUser($normalizedPhone, $email);
        }

        // Check if SMS/Email is enabled BEFORE generating OTP (early validation)
        if ($normalizedPhone) {
            $this->validateSmsEnabled($ownershipId);
        } elseif ($email) {
            $this->validateEmailEnabled($ownershipId);
        }

        // Check rate limit
        $identifier = $normalizedPhone ?? $email;
        $this->checkRateLimit($identifier, $purpose);

        // Generate 6-digit OTP
        $otp = $this->generateOtp();

        // Generate session ID
        $sessionId = Str::uuid()->toString();

        // Calculate expiry (10 minutes)
        $expiresAt = now()->addMinutes(10);

        // Save to database
        $otpRecord = OtpVerification::create([
            'phone' => $normalizedPhone,
            'email' => $email,
            'otp' => $otp,
            'purpose' => $purpose,
            'session_id' => $sessionId,
            'expires_at' => $expiresAt,
            'attempts' => 0,
        ]);

        // Log OTP generation in local environment
        if (app()->environment(['local', 'testing'])) {
            Log::info('OTP generated (local environment)', [
                'otp' => $otp,
                'identifier' => $normalizedPhone ?? $email,
                'purpose' => $purpose,
                'session_id' => $sessionId,
                'expires_at' => $expiresAt->toIso8601String(),
                'ownership_id' => $ownershipId,
                'note' => 'In local environment, OTP is always 123456. Use this OTP to verify.',
                'settings_source' => $ownershipId ? 'ownership-specific (with system fallback)' : 'system-wide',
            ]);
        }

        // Send OTP via SMS or Email
        $sendResult = null;
        if ($normalizedPhone) {
            $sendResult = $this->smsService->sendOtp($normalizedPhone, $otp, $purpose, $ownershipId);
            if (!$sendResult) {
                throw ValidationException::withMessages([
                    'phone' => ['Failed to send OTP via SMS. Please check SMS settings or try email instead.'],
                ]);
            }
        } elseif ($email) {
            try {
                $this->mailService->sendOtpEmail($email, $otp, $purpose, $ownershipId);
                $sendResult = true;
            } catch (\Exception $e) {
                Log::error('Failed to send OTP email', [
                    'email' => $email,
                    'purpose' => $purpose,
                    'ownership_id' => $ownershipId,
                    'error' => $e->getMessage(),
                ]);
                throw ValidationException::withMessages([
                    'email' => ['Failed to send OTP via email. Please check email settings or try phone instead.'],
                ]);
            }
        }

        // Return session info
        return [
            'session_id' => $sessionId,
            'expires_at' => $otpRecord->expires_at->toIso8601String(),
        ];
    }

    /**
     * Verify OTP.
     *
     * @param string|null $phone
     * @param string|null $email
     * @param string $otp
     * @param string $purpose
     * @param string|null $sessionId
     * @param bool $allowVerified Allow already verified OTPs (for reset password after verification)
     * @return bool
     */
    public function verify(?string $phone = null, ?string $email = null, string $otp, string $purpose, ?string $sessionId = null, bool $allowVerified = false): bool
    {
        if (!$phone && !$email) {
            return false;
        }

        // Normalize phone if provided
        $normalizedPhone = $phone ? $this->normalizePhone($phone) : null;

        // Find OTP
        $query = OtpVerification::where('purpose', $purpose)
            ->where('otp', $otp);

        // If not allowing verified OTPs, use valid scope (non-expired, non-verified)
        if (!$allowVerified) {
            $query->valid();
        } else {
            // Allow verified OTPs but still check expiration
            $query->where('expires_at', '>', now());
        }

        if ($normalizedPhone) {
            $query->where('phone', $normalizedPhone);
        } elseif ($email) {
            $query->where('email', $email);
        }

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $otpRecord = $query->first();

        if (!$otpRecord) {
            // Increment attempts if OTP exists but wrong
            $this->incrementFailedAttempts($normalizedPhone, $email, $purpose);
            
            // Log failed verification in local environment
            if (app()->environment(['local', 'testing'])) {
                Log::warning('OTP verification failed (local environment)', [
                    'otp' => $otp,
                    'identifier' => $normalizedPhone ?? $email,
                    'purpose' => $purpose,
                    'session_id' => $sessionId,
                    'reason' => 'OTP not found or invalid',
                    'note' => 'In local environment, OTP should be 123456',
                ]);
            }
            
            return false;
        }

        // Mark as verified only if not already verified
        if (!$otpRecord->isVerified()) {
            $otpRecord->markAsVerified();
        }

        // Clear rate limit
        $this->clearRateLimit($normalizedPhone ?? $email, $purpose);

        // Log successful verification in local environment
        if (app()->environment(['local', 'testing'])) {
            Log::info('OTP verified successfully (local environment)', [
                'otp' => $otp,
                'identifier' => $normalizedPhone ?? $email,
                'purpose' => $purpose,
                'session_id' => $sessionId,
                'verified_at' => $otpRecord->verified_at?->toIso8601String(),
            ]);
        }

        return true;
    }

    /**
     * Generate 6-digit OTP.
     * In local environment, always return '123456' for testing.
     */
    private function generateOtp(): string
    {
        // In local/testing environment, always return fixed OTP for testing
        if (app()->environment(['local', 'testing'])) {
            return '123456';
        }

        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Normalize phone number to +966 format.
     */
    private function normalizePhone(string $phone): string
    {
        return \App\Rules\SaudiPhoneNumber::normalize($phone);
    }

    /**
     * Check rate limit.
     *
     * @throws ValidationException
     */
    private function checkRateLimit(string $identifier, string $purpose): void
    {
        $key = "otp:{$identifier}:{$purpose}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'identifier' => ["Too many OTP requests. Please try again in {$seconds} seconds."],
            ]);
        }

        RateLimiter::hit($key, 300); // 5 minutes
    }

    /**
     * Clear rate limit.
     */
    private function clearRateLimit(string $identifier, string $purpose): void
    {
        $key = "otp:{$identifier}:{$purpose}";
        RateLimiter::clear($key);
    }

    /**
     * Increment failed attempts.
     */
    private function incrementFailedAttempts(?string $phone, ?string $email, string $purpose): void
    {
        $query = OtpVerification::where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest();

        if ($phone) {
            $query->where('phone', $phone);
        } elseif ($email) {
            $query->where('email', $email);
        }

        $otpRecord = $query->first();

        if ($otpRecord) {
            $otpRecord->incrementAttempts();
        }
    }

    /**
     * Get ownership ID from user's default ownership.
     * Returns null if user doesn't exist, is Super Admin, or has no default ownership.
     * Null means system-wide settings will be used.
     *
     * @param string|null $phone
     * @param string|null $email
     * @return int|null
     */
    private function getOwnershipIdFromUser(?string $phone, ?string $email): ?int
    {
        // Get user by phone or email
        $user = $phone 
            ? $this->userRepository->findByPhone($phone)
            : ($email ? $this->userRepository->findByEmail($email) : null);

        if (app()->environment(['local', 'testing'])) {
            Log::info('getUser', [
                'user' => $user ? [
                    'id' => $user->id,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'type' => $user->type,
                    'is_super_admin' => $user->isSuperAdmin(),
                ] : null,
            ]);
        }

        // If user doesn't exist or is Super Admin, return null (use system settings)
        if (!$user || $user->isSuperAdmin()) {
            return null;
        }

        // Get ownership_id directly from mapping (more efficient and reliable)
        $mapping = \App\Models\V1\Ownership\UserOwnershipMapping::where('user_id', $user->id)
            ->where('default', true)
            ->first();

        if (!$mapping) {
            // If no default mapping, get first mapping
            $mapping = \App\Models\V1\Ownership\UserOwnershipMapping::where('user_id', $user->id)
                ->first();
        }

        if (app()->environment(['local', 'testing'])) {
            Log::info('getOwnershipIdFromUser debug', [
                'user_id' => $user->id,
                'mapping_exists' => $mapping !== null,
                'mapping_id' => $mapping?->id,
                'ownership_id' => $mapping?->ownership_id,
                'is_default' => $mapping?->default,
                'mappings_count' => \App\Models\V1\Ownership\UserOwnershipMapping::where('user_id', $user->id)->count(),
            ]);
        }

        // Return ownership ID if exists, otherwise null (use system settings)
        return $mapping?->ownership_id;
    }

    /**
     * Validate that SMS is enabled before generating OTP.
     *
     * @param int|null $ownershipId
     * @throws ValidationException
     */
    private function validateSmsEnabled(?int $ownershipId): void
    {
        if (!$this->smsService->isSmsEnabled($ownershipId)) {
            $settingsSource = $ownershipId ? 'ownership-specific' : 'system-wide';
            throw ValidationException::withMessages([
                'phone' => ["SMS notifications are disabled in {$settingsSource} settings. Please contact administrator or use email instead."],
            ]);
        }
    }

    /**
     * Validate that Email is enabled before generating OTP.
     *
     * @param int|null $ownershipId
     * @throws ValidationException
     */
    private function validateEmailEnabled(?int $ownershipId): void
    {
        if (!$this->mailService->isEmailEnabled($ownershipId)) {
            $settingsSource = $ownershipId ? 'ownership-specific' : 'system-wide';
            throw ValidationException::withMessages([
                'email' => ["Email notifications are disabled in {$settingsSource} settings. Please contact administrator or use phone instead."],
            ]);
        }
    }
}

