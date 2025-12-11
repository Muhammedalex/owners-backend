# Media Files Module

## Overview
Media file management system for storing images, videos, and other media associated with various entities (ownerships, portfolios, buildings, units, etc.).

## What Needs to Be Done

### Database
- Create `media_files` table migration
- Link to `ownerships` table
- Polymorphic relationship (entity_type, entity_id) for flexible associations
- Store file path, name, size, MIME type
- Support file types (image, video, document, etc.)
- Track uploader and visibility (public/private)
- Display order support

### Models
- Create `MediaFile` model
- Polymorphic relationship: `morphTo('entity')`
- Relationship: `belongsTo(Ownership)`
- Relationship: `belongsTo(User)` for uploaded_by

### API Endpoints
- Upload media (POST `/api/v1/media`)
- Get media (GET `/api/v1/media/{id}`)
- Update media (PUT `/api/v1/media/{id}`)
- Delete media (DELETE `/api/v1/media/{id}`)
- List media by entity (GET `/api/v1/media?entity_type=X&entity_id=Y`)
- Reorder media

### Features
- File upload handling
- Multiple file types support
- Image resizing/optimization
- File storage (local/S3)
- Public/private visibility control
- Display order management
- File size and type validation

### Business Rules
- Files must be associated with an ownership
- Files can be linked to any entity type
- Public files are accessible without authentication
- Private files require ownership access
- File size limits apply
- Only allowed MIME types accepted

