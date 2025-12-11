# Documents Module

## Overview
Document storage and management system for storing contracts, certificates, legal documents, and other important files.

## What Needs to Be Done

### Database
- Create `documents` table migration
- Link to `ownerships` table
- Store document type, title, description
- File path, size, MIME type
- Polymorphic relationship (entity_type, entity_id) for flexible associations
- Track uploader and visibility
- Support document expiration dates

### Models
- Create `Document` model
- Polymorphic relationship: `morphTo('entity')`
- Relationship: `belongsTo(Ownership)`
- Relationship: `belongsTo(User)` for uploaded_by

### API Endpoints
- Upload document (POST `/api/v1/documents`)
- Get document (GET `/api/v1/documents/{id}`)
- Download document (GET `/api/v1/documents/{id}/download`)
- Update document (PUT `/api/v1/documents/{id}`)
- Delete document (DELETE `/api/v1/documents/{id}`)
- List documents (GET `/api/v1/documents`)
- List documents by entity (GET `/api/v1/documents?entity_type=X&entity_id=Y`)

### Features
- Document upload and storage
- Document type categorization
- Expiration date tracking
- Public/private visibility
- Document download with access control
- File validation and security

### Business Rules
- Documents must be associated with an ownership
- Documents can be linked to any entity type
- Private documents require ownership access
- Expired documents can be flagged/archived
- Document types: contract, certificate, legal, id_document, etc.

