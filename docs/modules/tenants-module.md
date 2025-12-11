# Tenants Module

## Overview
Tenant management system for tracking tenant information, identification, emergency contacts, and credit ratings.

## What Needs to Be Done

### Database
- Create `tenants` table migration
- Link to `users` table (one-to-one relationship)
- Link to `ownerships` table
- Store national ID, ID documents, expiry dates
- Track emergency contacts
- Store employment and income information
- Credit rating system
- **Optional: Payment tracking feature** - Track if tenant pays on time (can be disabled)
  - Payment history tracking (optional feature)
  - Payment behavior monitoring (optional)

### Models
- Create `Tenant` model
- Relationship: `belongsTo(User)`
- Relationship: `belongsTo(Ownership)`
- Handle ID document file storage

### API Endpoints
- Create tenant (POST `/api/v1/tenants`)
- Get tenant details (GET `/api/v1/tenants/{id}`)
- Update tenant (PUT `/api/v1/tenants/{id}`)
- List tenants (GET `/api/v1/tenants`)
- Upload ID document

### Features
- Tenant profile management
- ID document upload and validation
- Emergency contact management
- Credit rating tracking
- Employment information tracking
- **Optional payment tracking** - Monitor tenant payment behavior (can be enabled/disabled)
  - Track payment history
  - Payment compliance monitoring
  - Late payment tracking

### Business Rules
- One user can only have one tenant record per ownership
- ID documents must have expiry dates
- Credit ratings affect contract approval
- **Payment tracking is optional** - Can be enabled/disabled per ownership
- Payment tracking helps monitor tenant payment compliance

