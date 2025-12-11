# Database Changes Required

## Summary of Changes Needed Based on Business Requirements

### 1. Contracts Table - Add Ejar Code

**Required Change:**
- Add `ejar_code` field to `contracts` table
- Type: `varchar(100)` or `varchar(255)`
- Nullable: `YES` (optional field)
- Description: Saudi rental platform (ejar.sa) registration code
- Index: Optional (for searching by ejar code)

**Reason:**
- Contracts can be registered on Saudi ejar platform
- Old contracts or unregistered contracts don't have ejar codes
- Field must be optional to support both cases

**ERD Status:** ❌ Missing - Needs to be added

---

### 2. Payments Table - Simplify Structure

**Current ERD Fields (to keep):**
- `id`, `invoice_id`, `ownership_id`
- `method` (cash, bank transfer, etc.)
- `amount`, `currency` (default SAR)
- `status` (simplify to: paid/unpaid/pending)
- `paid_at`, `confirmed_by`
- `created_at`, `updated_at`

**Current ERD Fields (to remove or make optional):**
- `transaction_id` - Keep but make optional (for manual reference)
- `gateway_name` - ❌ Remove (no gateway integration)
- `gateway_transaction_ref` - ❌ Remove (no gateway integration)

**ERD Status:** ⚠️ Needs simplification - Remove gateway-related fields

---

### 3. Invoices Table - No Changes Needed

**Current ERD Status:** ✅ OK
- Structure is fine
- Just need to clarify in code/logic that invoices are optional/on-demand
- No database changes required

---

### 4. Tenants Table - Payment Tracking (Optional Feature)

**Current ERD Fields:** ✅ Basic structure is OK

**Optional Enhancement (can be added later):**
- Consider adding payment tracking fields if needed:
  - `payment_tracking_enabled` (boolean) - Enable/disable per tenant
  - Or handle via system_settings or ownership settings

**ERD Status:** ✅ OK - Can use existing structure, payment tracking can be handled via relationships with payments table

---

## Migration Priority

1. **High Priority:**
   - Add `ejar_code` to contracts table
   - Simplify payments table (remove gateway fields)

2. **Low Priority:**
   - Payment tracking for tenants (can be handled via existing relationships)

---

## Notes

- All payments are **external** - System only records status
- No payment gateway integration needed
- Invoices are **optional/on-demand** - Not automatically generated
- Ejar code is **optional** - Supports both registered and unregistered contracts

