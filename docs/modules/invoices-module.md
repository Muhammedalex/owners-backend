# Invoices Module

## Overview
**Optional billing and invoicing system** - Invoices are generated **on-demand/request**, not automatically. System supports manual invoice creation when needed.

## What Needs to Be Done

### Database
- Create `invoices` table migration
- Link to `contracts` and `ownerships` tables
- Store billing period (start/end dates)
- Calculate tax (15% VAT default for Saudi Arabia)
- Track invoice status (draft → sent → paid → overdue → cancelled)
- Store due dates and payment timestamps
- Link to invoice items

### Models
- Create `Invoice` model
- Relationships: `belongsTo(Contract)`, `belongsTo(Ownership)`
- Relationship: `hasMany(InvoiceItem)`
- Relationship: `hasMany(Payment)`
- Auto-calculate totals (amount + tax = total)

### API Endpoints
- Generate invoice (POST `/api/v1/invoices`)
- Get invoice (GET `/api/v1/invoices/{id}`)
- Update invoice (PUT `/api/v1/invoices/{id}`)
- List invoices (GET `/api/v1/invoices`)
- Mark as paid
- Send invoice (email/notification)
- Cancel invoice

### Features
- **Manual/On-demand invoice generation** - Not automatic
- Generate invoices when requested by user
- Tax calculation (15% VAT)
- Invoice numbering system
- Billing period management
- Due date tracking
- Invoice status workflow
- PDF generation support
- **Invoices are optional** - Not required for all contracts

### Business Rules
- Invoices are linked to contracts
- Invoice number must be unique
- Tax rate defaults to 15% (Saudi VAT)
- Total = amount + tax
- **Invoices are generated on-demand** - Not automatically created
- **Invoices are optional** - Contracts can exist without invoices
- Invoices can be generated for specific periods when requested
- Only draft invoices can be modified

