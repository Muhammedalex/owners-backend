<?php

namespace App\Services\V1\Auth;

use App\Models\V1\Auth\PersonalAccessToken;
use App\Models\V1\Auth\User;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;

class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user.
     */
    public function register(array $data): array
    {
        $user = $this->userRepository->create($data);

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        // Generate tokens
        $tokens = $user->generateTokens($data['device_name'] ?? 'default');

        return [
            'user' => $user,
            'tokens' => $tokens,
        ];
    }

    /**
     * Login user.
     */
    public function login(array $credentials, string $deviceName = null): array
    {
        $identifier = $credentials['email'] ?? $credentials['phone'] ?? null;
        $password = $credentials['password'];

        if (!$identifier) {
            throw ValidationException::withMessages([
                'email' => ['Email or phone is required.'],
            ]);
        }

        // Rate limiting
        $key = 'login:' . $identifier;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        // Find user by email or phone
        $user = $this->userRepository->findByEmail($identifier)
            ?? $this->userRepository->findByPhone($identifier);

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($key, 60);
            
            if ($user) {
                $user->incrementAttempts();
            }

            throw new AuthenticationException('Invalid credentials.');
        }

        // Check if user is active
        if (!$user->isActive()) {
            throw new AuthenticationException('Your account has been deactivated.');
        }

        // Reset attempts and record login
        RateLimiter::clear($key);
        $user->resetAttempts();
        $user->recordLogin();

        // Get default ownership UUID (if user is not Super Admin)
        $defaultOwnershipUuid = null;
        if (!$user->isSuperAdmin()) {
            $defaultOwnership = $user->getDefaultOwnership();
            if ($defaultOwnership) {
                $defaultOwnershipUuid = $defaultOwnership->uuid;
            } else {
                // Non-Super Admin users must have at least one ownership
                throw new AuthenticationException('Your account is not linked to any ownership. Please contact your administrator.');
            }
        }

        // Generate tokens
        $tokens = $user->generateTokens($deviceName);

        return [
            'user' => $user,
            'tokens' => $tokens,
            'default_ownership_uuid' => $defaultOwnershipUuid,
        ];
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(string $refreshToken): array
    {
        $hashedRefreshToken = hash('sha256', $refreshToken);

        // Find the token with the refresh token
        $token = PersonalAccessToken::where('refresh_token', $hashedRefreshToken)
            ->where('refresh_token_expires_at', '>', now())
            ->first();

        if (!$token) {
            throw new AuthenticationException('Invalid or expired refresh token.');
        }

        // Get the user from the token
        $user = $token->tokenable;

        if (!$user instanceof User) {
            throw new AuthenticationException('Invalid token.');
        }

        // Check if user is active
        if (!$user->isActive()) {
            throw new AuthenticationException('Your account has been deactivated.');
        }

        // Refresh the token using the user's method
        $tokens = $user->refreshAccessToken($refreshToken);

        if (!$tokens) {
            throw new AuthenticationException('Invalid or expired refresh token.');
        }

        // Get default ownership UUID (if user is not Super Admin)
        $defaultOwnershipUuid = null;
        if (!$user->isSuperAdmin()) {
            $defaultOwnership = $user->getDefaultOwnership();
            if ($defaultOwnership) {
                $defaultOwnershipUuid = $defaultOwnership->uuid;
            }
        }

        return [
            'user' => $user,
            'tokens' => $tokens,
            'default_ownership_uuid' => $defaultOwnershipUuid,
        ];
    }

    /**
     * Logout user.
     */
    public function logout(?string $refreshToken = null): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        if ($refreshToken) {
            $user->revokeTokenByRefreshToken($refreshToken);
        } else {
            // Revoke current token
            $user->currentAccessToken()?->delete();
        }
    }

    /**
     * Logout from all devices.
     */
    public function logoutAll(): void
    {
        Auth::user()?->revokeAllTokens();
    }

    /**
     * Verify email.
     */
    public function verifyEmail(int $userId, string $hash): bool
    {
        $user = $this->userRepository->find($userId);

        if (!$user || !hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return false;
        }

        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->markEmailAsVerified();

        return true;
    }

    /**
     * Resend email verification.
     */
    public function resendEmailVerification(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email already verified.'],
            ]);
        }

        $user->sendEmailVerificationNotification();
    }

    /**
     * Create refresh token cookie.
     */
    public function createRefreshTokenCookie(string $refreshToken): Cookie
    {
        $expiry = now()->addDays(config('sanctum.refresh_expiration', 30));
        
        // Create cookie using Symfony Cookie class directly
        return Cookie::create(
            'refresh_token',
            $refreshToken,
            $expiry->getTimestamp(),
            '/',
            null,
            config('app.env') === 'production', // secure - true in production
            true,  // httpOnly
            false, // raw
            'strict'  // sameSite - 'strict' provides better CSRF protection than 'lax'
        );
    }

    /**
     * Clear refresh token cookie.
     */
    public function clearRefreshTokenCookie(): Cookie
    {
        // Create an expired cookie to clear it
        return Cookie::create(
            'refresh_token',
            null,
            time() - 3600, // Expire in the past
            '/',
            null,
            config('app.env') === 'production', // secure
            true,  // httpOnly
            false, // raw
            'strict'  // sameSite
        );
    }

    /**
     * Create ownership UUID cookie.
     */
    public function createOwnershipCookie(string $ownershipUuid): Cookie
    {
        $expiry = now()->addDays(30); // 30 days
        
        return Cookie::create(
            'ownership_uuid',
            $ownershipUuid,
            $expiry->getTimestamp(),
            '/',
            null,
            config('app.env') === 'production', // secure - true in production
            true,  // httpOnly
            false, // raw
            'strict'  // sameSite
        );
    }

    /**
     * Clear ownership UUID cookie.
     */
    public function clearOwnershipCookie(): Cookie
    {
        // Create an expired cookie to clear it
        return Cookie::create(
            'ownership_uuid',
            null,
            time() - 3600, // Expire in the past
            '/',
            null,
            config('app.env') === 'production', // secure
            true,  // httpOnly
            false, // raw
            'strict'  // sameSite
        );
    }
}

