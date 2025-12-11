<?php

namespace App\Models\V1\Media;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaFile extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ownership_id',
        'entity_type',
        'entity_id',
        'type',
        'path',
        'name',
        'size',
        'mime',
        'title',
        'description',
        'order',
        'uploaded_by',
        'public',
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
            'order' => 'integer',
            'public' => 'boolean',
        ];
    }

    /**
     * Get the ownership that owns this media file.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the entity that owns this media file (polymorphic).
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this media file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope a query to only include public media files.
     */
    public function scopePublic($query)
    {
        return $query->where('public', true);
    }

    /**
     * Scope a query to only include private media files.
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
     * Scope a query to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    /**
     * Get the full URL to the media file.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * Check if media file is public.
     */
    public function isPublic(): bool
    {
        return $this->public === true;
    }

    /**
     * Check if media file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime ?? '', 'image/');
    }

    /**
     * Check if media file is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime ?? '', 'video/');
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

