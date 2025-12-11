<?php

namespace App\Models\V1\Contract;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTerm extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contract_terms';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        'key',
        'value',
        'type',
    ];

    /**
     * Get the contract that owns this term.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * Scope a query to filter by key.
     */
    public function scopeWithKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeWithType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

