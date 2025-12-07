<?php

namespace App\Traits\V1\Auth;

use App\Models\V1\Auth\PersonalAccessToken;
use Illuminate\Support\Str;

trait GeneratesTokens
{
    /**
     * Generate access and refresh tokens.
     */
    public function generateTokens(string $deviceName = null): array
    {
        // Revoke existing tokens for this device (optional - for single device login)
        // $this->tokens()->where('device_name', $deviceName)->delete();

        // Create access token (short-lived)
        $accessToken = $this->createToken(
            $deviceName ?? 'default',
            ['*'],
            now()->addMinutes(config('sanctum.expiration', 60))
        );

        // Generate refresh token
        $refreshToken = $this->generateRefreshToken($accessToken->accessToken);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 60) * 60, // in seconds
        ];
    }

    /**
     * Generate a refresh token.
     */
    protected function generateRefreshToken(PersonalAccessToken $accessToken): string
    {
        $refreshToken = Str::random(64);
        $hashedRefreshToken = hash('sha256', $refreshToken);

        // Use forceFill to ensure fields are saved even if not in fillable
        $accessToken->forceFill([
            'refresh_token' => $hashedRefreshToken,
            'refresh_token_expires_at' => now()->addDays(config('sanctum.refresh_expiration', 30)),
            'device_name' => $accessToken->name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ])->save();

        return $refreshToken;
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $hashedRefreshToken = hash('sha256', $refreshToken);

        $token = $this->tokens()
            ->where('refresh_token', $hashedRefreshToken)
            ->where('refresh_token_expires_at', '>', now())
            ->first();

        if (!$token) {
            return null;
        }

        // Store device name before deleting
        $deviceName = $token->name;

        // Revoke old token
        $token->delete();

        // Generate new tokens with the same device name
        return $this->generateTokens($deviceName);
    }

    /**
     * Revoke all tokens for the user.
     */
    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }

    /**
     * Revoke token by refresh token.
     */
    public function revokeTokenByRefreshToken(string $refreshToken): bool
    {
        $hashedRefreshToken = hash('sha256', $refreshToken);

        return $this->tokens()
            ->where('refresh_token', $hashedRefreshToken)
            ->delete() > 0;
    }
}

