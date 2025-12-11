<?php

namespace App\Models\V1\Document;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ownership_id',
        'type',
        'title',
        'description',
        'path',
        'size',
        'mime',
        'entity_type',
        'entity_id',
        'uploaded_by',
        'public',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'public' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the ownership that owns this document.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the entity that owns this document (polymorphic).
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope a query to only include public documents.
     */
    public function scopePublic($query)
    {
        return $query->where('public', true);
    }

    /**
     * Scope a query to only include private documents.
     */
    public function scopePrivate($query)
    {
        return $query->where('public', false);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by ownership.
     */
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('ownership_id', $ownershipId);
    }

    /**
     * Scope a query to filter expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope a query to filter non-expired documents.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
        });
    }

    /**
     * Get the full URL to the document.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * Check if document is public.
     */
    public function isPublic(): bool
    {
        return $this->public === true;
    }

    /**
     * Check if document is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if document will expire soon (within 30 days).
     */
    public function expiresSoon(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isFuture() && $this->expires_at->diffInDays(now()) <= 30;
    }

    /**
     * Get file size in human readable format.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

