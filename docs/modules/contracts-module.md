# Contracts Module

## Overview
Rental contract management system for managing lease agreements between tenants and property units.

## What Needs to Be Done

### Database
- Create `contracts` table migration
- Link to `units`, `tenants`, `ownerships` tables
- Support contract versioning (parent_id for contract renewals)
- Store contract dates, rent amounts, payment frequency
- **Store `ejar_code` (optional)** - Saudi rental platform (ejar.sa) registration code
  - Can be empty for old contracts or unregistered contracts
  - Not all contracts are registered on ejar platform
- Track security deposits
- Store contract documents and digital signatures
- Contract status workflow (draft → active → expired → terminated)

### Models
- Create `Contract` model
- Relationships: `belongsTo(Unit)`, `belongsTo(Tenant)`, `belongsTo(Ownership)`
- Self-referencing relationship for contract versions
- Relationship: `hasMany(ContractTerm)`
- Relationship: `hasMany(Invoice)`

### API Endpoints
- Create contract (POST `/api/v1/contracts`)
- Get contract (GET `/api/v1/contracts/{id}`)
- Update contract (PUT `/api/v1/contracts/{id}`)
- List contracts (GET `/api/v1/contracts`)
- Renew contract (create new version)
- Approve contract
- Terminate contract
- Upload contract document

### Features
- Contract creation and management
- Contract versioning/renewal system
- **Ejar platform integration** - Optional registration code storage
- Digital signature support
- Contract document storage
- Status workflow management
- Deposit tracking

### Business Rules
- One unit can only have one active contract at a time
- Contracts must have start and end dates
- Rent amount is required
- Contract approval required before activation
- Contract renewals create new versions linked to parent
- **Ejar code is optional** - Contracts can exist without ejar registration
- Old contracts or unregistered contracts don't require ejar_code

