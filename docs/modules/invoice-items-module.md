# Invoice Items Module

## Overview
Line items for invoices. Break down invoice charges into individual items (rent, utilities, maintenance, etc.).

## What Needs to Be Done

### Database
- Create `invoice_items` table migration
- Link to `invoices` table
- Store item type, description, quantity, unit price
- Calculate total per item (quantity × unit_price)

### Models
- Create `InvoiceItem` model
- Relationship: `belongsTo(Invoice)`

### API Endpoints
- Add item to invoice (POST `/api/v1/invoices/{id}/items`)
- Update item (PUT `/api/v1/invoices/{id}/items/{itemId}`)
- Delete item (DELETE `/api/v1/invoices/{id}/items/{itemId}`)
- List invoice items (GET `/api/v1/invoices/{id}/items`)

### Features
- Multiple line items per invoice
- Item types (rent, utilities, maintenance, penalty, etc.)
- Quantity and unit price support
- Automatic total calculation

### Business Rules
- Items belong to invoices
- Total = quantity × unit_price
- Invoice total should sum all item totals
- Items can be added/removed from draft invoices only

