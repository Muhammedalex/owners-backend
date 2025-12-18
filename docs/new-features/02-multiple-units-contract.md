# Feature 2: Multiple Units per Contract

## Overview

Enable a single tenant to rent multiple units under one contract. This is common for businesses or individuals who need multiple spaces (e.g., multiple offices, shops, or warehouses).

---

## Business Requirements

### User Stories

1. **As an Owner**, I want to create a contract that includes multiple units for one tenant.
2. **As a Tenant**, I want to rent multiple units under a single contract for easier management.
3. **As a System**, I want to track which units are included in each contract and ensure no unit conflicts.

### Key Requirements

- ✅ One contract can include multiple units
- ✅ One unit can only have one active contract at a time
- ✅ Rent can be calculated per unit or as a total amount
- ✅ Invoice generation supports multiple units
- ✅ Contract renewal maintains unit relationships
- ✅ Unit availability tracking

---

## Workflow

### 1. Create Contract with Multiple Units

```
Owner → Contracts → Create Contract
  ↓
Select Tenant
  ↓
Select Multiple Units (checkbox list)
  ↓
System validates:
  - All units belong to same ownership
  - All units are available
  - No unit has active contract
  ↓
Enter contract details:
  - Contract number
  - Start/End dates
  - Rent amount (total or per unit)
  - Payment frequency
  - Deposit
  - Ejar code (optional)
  ↓
Save contract
  ↓
System creates:
  - Contract record
  - Contract-Unit relationships
  ↓
Mark units as "rented"
  ↓
Contract created successfully
```

### 2. View Contract with Units

```
Owner/Tenant → Contracts → View Contract
  ↓
Display contract details
  ↓
Display list of units:
  - Unit number
  - Unit type
  - Building/Floor
  - Unit area
  - Unit-specific rent (if applicable)
  ↓
Show total rent
Show unit count
```

### 3. Invoice Generation

```
System generates invoice for contract
  ↓
Check contract units
  ↓
Create invoice items:
  - One item per unit (if per-unit pricing)
  - OR one item for total rent
  ↓
Calculate totals
  ↓
Invoice includes all units in description
```

---

## Database Design

### New Table: `contract_units`

**Pivot table** linking contracts to multiple units.

```sql
CREATE TABLE contract_units (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    contract_id BIGINT NOT NULL,
    unit_id BIGINT NOT NULL,
    rent_amount DECIMAL(12, 2) NULLABLE, -- Per-unit rent (if different from contract total)
    notes TEXT NULLABLE, -- Unit-specific notes
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_contract_unit (contract_id, unit_id),
    INDEX idx_contract_id (contract_id),
    INDEX idx_unit_id (unit_id)
);
```

### Migration File

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_contract_units_table.php`

### Contract Model Changes

**No changes needed** - Use pivot relationship.

---

## Models

### Contract Model Updates

**File:** `app/Models/V1/Contract/Contract.php`

**Add Relationship:**

```php
/**
 * Get the units for this contract (many-to-many).
 */
public function units(): BelongsToMany
{
    return $this->belongsToMany(Unit::class, 'contract_units', 'contract_id', 'unit_id')
        ->withPivot('rent_amount', 'notes')
        ->withTimestamps();
}

/**
 * Get the primary unit (for backward compatibility).
 * Returns first unit or null.
 */
public function primaryUnit(): BelongsTo
{
    return $this->belongsTo(Unit::class, 'unit_id');
}

/**
 * Check if contract has multiple units.
 */
public function hasMultipleUnits(): bool
{
    return $this->units()->count() > 1;
}

/**
 * Get total units count.
 */
public function getUnitsCountAttribute(): int
{
    return $this->units()->count();
}
```

### Unit Model Updates

**File:** `app/Models/V1/Ownership/Unit.php`

**Add Relationship:**

```php
/**
 * Get the contracts for this unit (many-to-many).
 */
public function contracts(): BelongsToMany
{
    return $this->belongsToMany(Contract::class, 'contract_units', 'unit_id', 'contract_id')
        ->withPivot('rent_amount', 'notes')
        ->withTimestamps();
}

/**
 * Get active contract for this unit.
 */
public function activeContract(): ?Contract
{
    return $this->contracts()
        ->where('status', 'active')
        ->where('start', '<=', now())
        ->where('end', '>=', now())
        ->first();
}

/**
 * Check if unit is available.
 */
public function isAvailable(): bool
{
    return $this->status === 'available' && $this->activeContract() === null;
}
```

### ContractUnit Model (Pivot)

**File:** `app/Models/V1/Contract/ContractUnit.php`

```php
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
```

---

## Services

### ContractService Updates

**File:** `app/Services/V1/Contract/ContractService.php`

**Update `create()` method:**

```php
public function create(array $data): Contract
{
    return DB::transaction(function () use ($data) {
        // Extract unit_ids from data
        $unitIds = $data['unit_ids'] ?? [];
        unset($data['unit_ids']);

        // If single unit_id provided (backward compatibility)
        if (isset($data['unit_id']) && empty($unitIds)) {
            $unitIds = [$data['unit_id']];
        }

        // Validate units
        $this->validateUnits($unitIds, $data['ownership_id']);

        // Set primary unit_id (first unit) for backward compatibility
        $data['unit_id'] = $unitIds[0];

        // Create contract
        $contract = $this->contractRepository->create($data);

        // Attach units
        $this->attachUnits($contract, $unitIds, $data['unit_rents'] ?? []);

        // Update unit statuses
        $this->updateUnitStatuses($unitIds, 'rented');

        return $contract->load('units');
    });
}

private function validateUnits(array $unitIds, int $ownershipId): void
{
    $units = Unit::whereIn('id', $unitIds)
        ->where('ownership_id', $ownershipId)
        ->get();

    if ($units->count() !== count($unitIds)) {
        throw new \Exception('Some units not found or not in ownership');
    }

    // Check for active contracts
    foreach ($units as $unit) {
        if (!$unit->isAvailable()) {
            throw new \Exception("Unit {$unit->number} is not available");
        }
    }
}

private function attachUnits(Contract $contract, array $unitIds, array $unitRents = []): void
{
    $attachData = [];
    foreach ($unitIds as $index => $unitId) {
        $attachData[$unitId] = [
            'rent_amount' => $unitRents[$index] ?? null,
        ];
    }

    $contract->units()->attach($attachData);
}

private function updateUnitStatuses(array $unitIds, string $status): void
{
    Unit::whereIn('id', $unitIds)->update(['status' => $status]);
}
```

---

## API Endpoints

### Updated Contract Endpoints

**POST** `/api/v1/contracts`

**Request Body:**

```json
{
  "tenant_id": 1,
  "unit_ids": [1, 2, 3],  // NEW: Array of unit IDs
  "unit_id": 1,  // DEPRECATED: Use unit_ids instead (kept for backward compatibility)
  "unit_rents": [5000, 3000, 2000],  // Optional: Per-unit rent amounts
  "number": "CONTRACT-2025-001",
  "start": "2025-01-01",
  "end": "2025-12-31",
  "rent": 10000,  // Total rent (or sum of unit_rents)
  "payment_frequency": "monthly",
  "deposit": 20000,
  "ejar_code": "EJAR123456",
  "status": "draft"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Contract created successfully",
  "data": {
    "uuid": "contract-uuid",
    "number": "CONTRACT-2025-001",
    "tenant": {...},
    "units": [
      {
        "id": 1,
        "uuid": "unit-uuid-1",
        "number": "101",
        "type": "office",
        "building": {...},
        "pivot": {
          "rent_amount": 5000
        }
      },
      {
        "id": 2,
        "uuid": "unit-uuid-2",
        "number": "102",
        "type": "office",
        "building": {...},
        "pivot": {
          "rent_amount": 3000
        }
      }
    ],
    "units_count": 2,
    "rent": 10000,
    "status": "draft"
  }
}
```

### New Endpoint: Get Available Units

**GET** `/api/v1/contracts/available-units`

**Query Parameters:**
- `ownership_id` (from middleware)
- `building_id` (optional)
- `type` (optional)
- `search` (optional)

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "unit-uuid",
      "number": "101",
      "type": "office",
      "area": 50.5,
      "status": "available",
      "building": {...}
    }
  ]
}
```

---

## Request Validation

### StoreContractRequest Updates

**File:** `app/Http/Requests/V1/Contract/StoreContractRequest.php`

**Add Rules:**

```php
public function rules(): array
{
    $ownershipId = request()->input('current_ownership_id');
    
    return [
        // Accept either unit_ids (array) or unit_id (single) for backward compatibility
        'unit_ids' => [
            'sometimes',
            'required_without:unit_id',
            'array',
            'min:1',
            Rule::exists('units', 'id')->where(function ($query) use ($ownershipId) {
                return $query->where('ownership_id', $ownershipId);
            }),
        ],
        'unit_id' => [
            'sometimes',
            'required_without:unit_ids',
            'integer',
            Rule::exists('units', 'id')->where(function ($query) use ($ownershipId) {
                return $query->where('ownership_id', $ownershipId);
            }),
        ],
        'unit_rents' => [
            'nullable',
            'array',
            'size:' . (count(request()->input('unit_ids', [])) ?: 1),
        ],
        'unit_rents.*' => ['nullable', 'numeric', 'min:0'],
        // ... existing rules
    ];
}
```

---

## Invoice Generation Updates

### InvoiceService Updates

**File:** `app/Services/V1/Invoice/InvoiceService.php`

**Update invoice generation to handle multiple units:**

```php
public function generateFromContract(Contract $contract, array $period): Invoice
{
    $invoice = $this->create([
        'contract_id' => $contract->id,
        'ownership_id' => $contract->ownership_id,
        'period_start' => $period['start'],
        'period_end' => $period['end'],
        'due' => $period['due'],
        'amount' => $contract->rent,
        // ... other fields
    ]);

    // Create invoice items
    if ($contract->hasMultipleUnits()) {
        // Create one item per unit
        foreach ($contract->units as $unit) {
            $unitRent = $unit->pivot->rent_amount ?? ($contract->rent / $contract->units_count);
            
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'rent',
                'description' => "Rent for Unit {$unit->number} - {$unit->type}",
                'quantity' => 1,
                'unit_price' => $unitRent,
                'total' => $unitRent,
            ]);
        }
    } else {
        // Single unit - create one item
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'rent',
            'description' => "Rent for Unit {$contract->unit->number}",
            'quantity' => 1,
            'unit_price' => $contract->rent,
            'total' => $contract->rent,
        ]);
    }

    return $invoice->load('items');
}
```

---

## Business Rules

1. **Unit Availability:**
   - Unit must have status `available`
   - Unit must not have active contract
   - All units must belong to same ownership

2. **Rent Calculation:**
   - If `unit_rents` provided: Use per-unit amounts, sum must equal contract `rent`
   - If `unit_rents` not provided: Divide total `rent` equally among units
   - Contract `rent` field stores total amount

3. **Contract Status:**
   - When contract becomes `active`: All units marked as `rented`
   - When contract `expires` or `terminated`: Units marked as `available`

4. **Backward Compatibility:**
   - Single `unit_id` still supported
   - Contracts with single unit work as before
   - API accepts both `unit_id` and `unit_ids`

---

## Frontend Considerations

### Contract Creation Form

- **Unit Selection:**
  - Multi-select dropdown/checkbox list
  - Show unit details (number, type, area, building)
  - Filter by building/type
  - Show availability status
  - Disable unavailable units

- **Rent Input:**
  - Option 1: Total rent (divide equally)
  - Option 2: Per-unit rent (show input per unit)
  - Auto-calculate total

### Contract View

- **Units List:**
  - Display all units in contract
  - Show per-unit rent if applicable
  - Link to unit details
  - Show unit status

---

## Testing Scenarios

1. ✅ Create contract with multiple units
2. ✅ Create contract with single unit (backward compatibility)
3. ✅ Validate unit availability
4. ✅ Prevent duplicate unit assignment
5. ✅ Calculate rent correctly (total vs per-unit)
6. ✅ Generate invoice with multiple units
7. ✅ Update unit status on contract activation
8. ✅ Update unit status on contract expiration
9. ✅ Contract renewal maintains unit relationships
10. ✅ View contract shows all units

---

## Migration Strategy

### Backward Compatibility

1. **Existing Contracts:**
   - Keep `unit_id` field in contracts table
   - Create `contract_units` entry for existing contracts
   - Migration script:

```php
// Migration: populate_contract_units_from_existing_contracts.php
Contract::whereNotNull('unit_id')->each(function ($contract) {
    $contract->units()->attach($contract->unit_id, [
        'rent_amount' => $contract->rent,
    ]);
});
```

2. **API Compatibility:**
   - Accept both `unit_id` and `unit_ids`
   - If `unit_id` provided, convert to `unit_ids` array
   - Response always includes `units` array

---

## Implementation Checklist

- [ ] Create migration for `contract_units` table
- [ ] Create `ContractUnit` pivot model
- [ ] Update `Contract` model (add `units()` relationship)
- [ ] Update `Unit` model (add `contracts()` relationship)
- [ ] Update `ContractService` (handle multiple units)
- [ ] Update `ContractRepository` (load units relationship)
- [ ] Update `StoreContractRequest` (validate unit_ids)
- [ ] Update `ContractController` (handle unit_ids)
- [ ] Update `InvoiceService` (generate items for multiple units)
- [ ] Create migration to populate existing contracts
- [ ] Update contract resources (include units)
- [ ] Write tests
- [ ] Update API documentation
- [ ] Frontend integration

---

## Future Enhancements

1. **Unit-Specific Terms:** Add terms specific to each unit in contract
2. **Partial Unit Release:** Release some units while keeping others
3. **Unit Transfer:** Transfer units between contracts
4. **Unit History:** Track unit contract history
5. **Bulk Unit Operations:** Add/remove multiple units at once

