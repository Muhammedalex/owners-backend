# Payments Module

## Overview
**Payment recording system** - Only tracks payment status (paid/not paid). **NO electronic payment processing**. All payments are external to the system, only status is recorded.

## What Needs to Be Done

### Database
- Create `payments` table migration
- Link to `invoices` and `ownerships` tables
- **Simple payment status tracking** - Only record if payment was made or not
- Store payment method (cash, bank transfer, check, etc.) - for reference only
- **NO payment gateway integration** - All payments are external
- **NO transaction processing** - Only status recording
- Payment status: `paid` or `unpaid` (simple boolean-like status)
- Store currency (default SAR)
- Track payment timestamps and who confirmed the payment
- Optional: transaction reference number (for manual tracking)

### Models
- Create `Payment` model
- Relationships: `belongsTo(Invoice)`, `belongsTo(Ownership)`
- Relationship: `belongsTo(User)` for confirmed_by

### API Endpoints
- Record payment status (POST `/api/v1/payments`) - Mark invoice as paid/unpaid
- Get payment record (GET `/api/v1/payments/{id}`)
- Update payment status (PUT `/api/v1/payments/{id}`) - Change paid/unpaid status
- List payments (GET `/api/v1/payments`)
- Mark as paid (manual confirmation)
- Mark as unpaid (if payment was recorded incorrectly)
- **NO payment gateway endpoints** - All payments handled externally

### Features
- **Simple payment status recording** - Paid/Unpaid only
- Payment method tracking (for reference/documentation)
- Manual payment confirmation
- Payment history and records
- **NO payment processing** - All payments done outside system
- **NO gateway integration** - System only records status

### Business Rules
- Payments are linked to invoices
- **Simple status tracking** - Only records if payment was made externally
- Payment amount should match invoice total (or partial)
- Payment status can be updated by authorized users
- Payment confirmation requires authorized user
- Currency defaults to SAR (Saudi Riyal)
- **All actual payments happen outside the system** - System is for record-keeping only

