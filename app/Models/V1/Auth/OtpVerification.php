<?php

namespace App\Models\V1\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'otp_verifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone',
        'email',
        'otp',
        'purpose',
        'session_id',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Scope to get valid (non-expired, non-verified) OTPs.
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                    ->whereNull('verified_at')
                    ->where('attempts', '<', 5);
    }

    /**
     * Scope to filter by phone.
     */
    public function scopeForPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    /**
     * Scope to filter by email.
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to filter by purpose.
     */
    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Scope to filter by session ID.
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Check if OTP is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if OTP is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Mark OTP as verified.
     */
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    /**
     * Increment attempts counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}

