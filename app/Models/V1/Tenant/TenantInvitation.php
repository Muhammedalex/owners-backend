<?php

namespace App\Models\V1\Tenant;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Traits\V1\Auth\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class TenantInvitation extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenant_invitations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'ownership_id',
        'invited_by',
        'email',
        'phone',
        'name',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'tenant_id',
        'notes',
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
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Get the ownership associated with this invitation.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the user who sent this invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who accepted this invitation.
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Get the tenant created from this invitation (for single-use invitations with email/phone).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get all tenants created from this invitation (for multi-use invitations without email/phone).
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'invitation_id');
    }

    /**
     * Scope a query to only include pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include accepted invitations.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope a query to filter by ownership.
     */
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('ownership_id', $ownershipId);
    }

    /**
     * Check if invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Check if invitation is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if invitation is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Accept the invitation.
     * Only sets accepted_by if invitation has email or phone (user can self-register).
     * Invitations without email/phone can only be closed manually by authorized users.
     */
    public function accept(?int $acceptedByUserId = null): void
    {
        $updateData = [
            'status' => 'accepted',
            'accepted_at' => now(),
        ];

        // Only set accepted_by if invitation has email or phone (user self-registered)
        // Invitations without contact info are closed manually and don't have accepted_by
        if ($acceptedByUserId && ($this->email || $this->phone)) {
            $updateData['accepted_by'] = $acceptedByUserId;
        }

        $this->update($updateData);
    }

    /**
     * Cancel the invitation.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark invitation as expired.
     */
    public function markAsExpired(): void
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'expired']);
        }
    }

    /**
     * Get the invitation URL.
     */
    public function getInvitationUrl(): string
    {
        $frontendUrl = env('FRONTEND_URL', env('APP_URL', 'http://localhost'));
        return rtrim($frontendUrl, '/') . '/register/tenant?token=' . $this->token;
    }
}

