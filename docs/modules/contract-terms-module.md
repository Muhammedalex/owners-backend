# Contract Terms Module

## Overview
Additional terms and conditions storage for rental contracts. Flexible key-value system for custom contract clauses.

## What Needs to Be Done

### Database
- Create `contract_terms` table migration
- Link to `contracts` table
- Key-value storage with type support
- Unique constraint on (contract_id, key)

### Models
- Create `ContractTerm` model
- Relationship: `belongsTo(Contract)`

### API Endpoints
- Add term to contract (POST `/api/v1/contracts/{id}/terms`)
- Update term (PUT `/api/v1/contracts/{id}/terms/{termId}`)
- Delete term (DELETE `/api/v1/contracts/{id}/terms/{termId}`)
- List contract terms (GET `/api/v1/contracts/{id}/terms`)

### Features
- Flexible term storage (key-value pairs)
- Support different value types (text, number, boolean, date)
- Terms can be included in contract documents

### Business Rules
- Each contract can have multiple terms
- Unique key per contract (no duplicate keys)
- Terms are tied to contract lifecycle

