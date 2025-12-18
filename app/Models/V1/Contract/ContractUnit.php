<?php

namespace App\Models\V1\Contract;

use App\Models\V1\Ownership\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractUnit extends Model
{
    protected $table = 'contract_units';

    protected $fillable = [
        'contract_id',
        'unit_id',
        'rent_amount',
        'notes',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}


