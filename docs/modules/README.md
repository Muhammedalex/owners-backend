# Modules Documentation

This directory contains implementation guides for each module in the Ownership Management System.

## Phase 1 - Core Business Modules (Priority)

1. **[Tenants Module](./tenants-module.md)** - Tenant management and profile tracking
2. **[Contracts Module](./contracts-module.md)** - Rental contract management
3. **[Contract Terms Module](./contract-terms-module.md)** - Additional contract terms storage
4. **[Invoices Module](./invoices-module.md)** - Billing and invoicing system
5. **[Invoice Items Module](./invoice-items-module.md)** - Invoice line items
6. **[Payments Module](./payments-module.md)** - Payment processing and tracking

## Phase 2 - Supporting Features

7. **[Media Files Module](./media-files-module.md)** - Media file management
8. **[Documents Module](./documents-module.md)** - Document storage and management
9. **[System Settings Module](./system-settings-module.md)** - System-wide configuration

## Implementation Order

Start with **Phase 1** modules in the listed order, as they have dependencies:
- Tenants → Contracts → Contract Terms
- Contracts → Invoices → Invoice Items → Payments

**Phase 2** modules can be implemented in parallel or after Phase 1 completion.

## Related Documentation

- [ERD.md](../ERD.md) - Complete database schema
- [Ownership Workflow](./ownership-workflow.md) - Ownership management workflow
- [Property Structure Tables](./property-structure-tables.md) - Property hierarchy implementation
- [Implementation Summary](./implementation-summary.md) - **Saudi rental system requirements and database changes**
- [Database Changes Required](./database-changes-required.md) - Detailed database modifications needed

## Important Notes

⚠️ **Saudi Rental System Specific Requirements:**
- Contracts support optional **ejar.sa** registration codes
- Payments are **external only** - System only records status (no gateway integration)
- Invoices are **optional/on-demand** - Not automatically generated
- See [Implementation Summary](./implementation-summary.md) for details

