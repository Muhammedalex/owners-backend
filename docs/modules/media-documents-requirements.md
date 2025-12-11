# Media & Documents Requirements by Model

This document outlines what media files and documents each model needs.

---

## 1. Ownership Model

### Media Files Needed:
- **Logo** (`type: 'logo'`)
  - **Purpose**: Company/ownership logo
  - **File Types**: JPG, PNG, SVG
  - **Max Size**: 2MB
  - **Required**: No (optional)
  - **Public**: Yes (can be displayed publicly)
  - **Storage**: `media/ownerships/{ownership_id}/logo/`
  - **Polymorphic**: `entity_type: 'App\Models\V1\Ownership\Ownership'`, `entity_id: {ownership_id}`

### Documents Needed:
- **Registration Certificate** (`type: 'registration_certificate'`)
  - **Purpose**: Official registration document
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional
  - **Public**: No (private)
  - **Expiration**: No
  - **Storage**: `documents/ownerships/{ownership_id}/registration/`

- **Tax Certificate** (`type: 'tax_certificate'`)
  - **Purpose**: Tax identification certificate
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional
  - **Public**: No (private)
  - **Expiration**: Yes (may expire)
  - **Storage**: `documents/ownerships/{ownership_id}/tax/`

---

## 2. User Model

### Media Files Needed:
- **Avatar** (`type: 'avatar'`)
  - **Purpose**: User profile picture
  - **File Types**: JPG, PNG, GIF
  - **Max Size**: 1MB
  - **Required**: No (optional)
  - **Public**: Yes (can be displayed in profile)
  - **Storage**: `media/users/{user_id}/avatar/`
  - **Polymorphic**: `entity_type: 'App\Models\V1\Auth\User'`, `entity_id: {user_id}`
  - **Auto Resize**: Yes (200x200px thumbnail)

### Documents Needed:
- **ID Document** (`type: 'id_document'`)
  - **Purpose**: National ID or passport copy
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional (may be required for certain user types)
  - **Public**: No (private)
  - **Expiration**: Yes (if passport)
  - **Storage**: `documents/users/{user_id}/id/`

---

## 3. Tenant Model

### Media Files Needed:
- **Profile Photo** (`type: 'profile_photo'`)
  - **Purpose**: Tenant profile picture
  - **File Types**: JPG, PNG
  - **Max Size**: 1MB
  - **Required**: No (optional)
  - **Public**: No (private)
  - **Storage**: `media/tenants/{tenant_id}/profile/`
  - **Polymorphic**: `entity_type: 'App\Models\V1\Tenant\Tenant'`, `entity_id: {tenant_id}`

### Documents Needed:
- **National ID Document** (`type: 'national_id'`)
  - **Purpose**: Copy of national ID card
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Yes (if `id_verification_required` setting is enabled)
  - **Public**: No (private)
  - **Expiration**: Yes (linked to `id_expiry` field)
  - **Storage**: `documents/tenants/{tenant_id}/id/`
  - **Note**: This replaces the old `id_document` field in tenants table

- **Employment Certificate** (`type: 'employment_certificate'`)
  - **Purpose**: Proof of employment
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional
  - **Public**: No (private)
  - **Expiration**: Yes (may expire)
  - **Storage**: `documents/tenants/{tenant_id}/employment/`

- **Income Proof** (`type: 'income_proof'`)
  - **Purpose**: Salary certificate or bank statement
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional (if income verification needed)
  - **Public**: No (private)
  - **Expiration**: Yes (may expire)
  - **Storage**: `documents/tenants/{tenant_id}/income/`

---

## 4. Contract Model

### Media Files Needed:
- **Contract Images** (`type: 'contract_image'`)
  - **Purpose**: Photos of signed contract pages
  - **File Types**: JPG, PNG
  - **Max Size**: 5MB per image
  - **Required**: Optional
  - **Public**: No (private)
  - **Storage**: `media/contracts/{contract_id}/images/`
  - **Polymorphic**: `entity_type: 'App\Models\V1\Contract\Contract'`, `entity_id: {contract_id}`
  - **Multiple**: Yes (can have multiple images)
  - **Order**: Yes (display order matters)

### Documents Needed:
- **Contract Document** (`type: 'contract_document'`)
  - **Purpose**: Signed contract PDF or scanned document
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 10MB
  - **Required**: Yes (when contract is approved)
  - **Public**: No (private)
  - **Expiration**: No (permanent record)
  - **Storage**: `documents/contracts/{contract_id}/`
  - **Note**: This replaces the old `document` field in contracts table

- **Digital Signature** (`type: 'digital_signature'`)
  - **Purpose**: Digital signature file or image
  - **File Types**: PNG, SVG, PDF
  - **Max Size**: 500KB
  - **Required**: Optional (if digital signature is used)
  - **Public**: No (private)
  - **Expiration**: No
  - **Storage**: `documents/contracts/{contract_id}/signatures/`
  - **Note**: This replaces the old `signature` field in contracts table

- **Ejar Registration** (`type: 'ejar_registration'`)
  - **Purpose**: Ejar platform registration document
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional (if `ejar_code` is provided)
  - **Public**: No (private)
  - **Expiration**: No
  - **Storage**: `documents/contracts/{contract_id}/ejar/`

- **Deposit Receipt** (`type: 'deposit_receipt'`)
  - **Purpose**: Receipt for security deposit payment
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional (when deposit is paid)
  - **Public**: No (private)
  - **Expiration**: No
  - **Storage**: `documents/contracts/{contract_id}/deposits/`

---

## 5. Invoice Model

### Media Files Needed:
- None (invoices are documents, not media)

### Documents Needed:
- **Invoice PDF** (`type: 'invoice_pdf'`)
  - **Purpose**: Generated invoice PDF document
  - **File Types**: PDF
  - **Max Size**: 2MB
  - **Required**: Yes (auto-generated when invoice is sent)
  - **Public**: No (private)
  - **Expiration**: No (permanent record)
  - **Storage**: `documents/invoices/{invoice_id}/`
  - **Auto Generate**: Yes (system generates PDF from invoice data)

- **Invoice Attachments** (`type: 'invoice_attachment'`)
  - **Purpose**: Additional documents attached to invoice
  - **File Types**: PDF, JPG, PNG, DOC, DOCX
  - **Max Size**: 5MB per file
  - **Required**: No (optional)
  - **Public**: No (private)
  - **Expiration**: No
  - **Storage**: `documents/invoices/{invoice_id}/attachments/`
  - **Multiple**: Yes (can have multiple attachments)

---

## 6. Payment Model

### Media Files Needed:
- **Payment Receipt Image** (`type: 'receipt_image'`)
  - **Purpose**: Photo of payment receipt (bank transfer, cash receipt, etc.)
  - **File Types**: JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional (when payment is confirmed)
  - **Public**: No (private)
  - **Storage**: `media/payments/{payment_id}/receipts/`
  - **Polymorphic**: `entity_type: 'App\Models\V1\Payment\Payment'`, `entity_id: {payment_id}`

### Documents Needed:
- **Payment Receipt** (`type: 'payment_receipt'`)
  - **Purpose**: Official payment receipt or bank transfer document
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Yes (when payment is confirmed)
  - **Public**: No (private)
  - **Expiration**: No (permanent record)
  - **Storage**: `documents/payments/{payment_id}/receipts/`

- **Bank Statement** (`type: 'bank_statement'`)
  - **Purpose**: Bank statement showing payment transaction
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional (for bank transfers)
  - **Public**: No (private)
  - **Expiration**: No
  - **Storage**: `documents/payments/{payment_id}/bank/`

---

## 7. Building Model

### Media Files Needed:
- **Building Photos** (`type: 'building_photo'`)
  - **Purpose**: Exterior and interior photos of the building
  - **File Types**: JPG, PNG
  - **Max Size**: 5MB per image
  - **Required**: No (optional)
  - **Public**: Yes (can be displayed in listings)
  - **Storage**: `media/buildings/{building_id}/photos/`
  - **Polymorphic**: `entity_type: 'App\Models\V1\Ownership\Building'`, `entity_id: {building_id}`
  - **Multiple**: Yes (can have multiple photos)
  - **Order**: Yes (display order matters - first photo is main/cover)
  - **Auto Resize**: Yes (generate thumbnails)

- **Building Plan** (`type: 'building_plan'`)
  - **Purpose**: Floor plans or architectural drawings
  - **File Types**: JPG, PNG, PDF
  - **Max Size**: 10MB
  - **Required**: No (optional)
  - **Public**: Yes (can be displayed)
  - **Storage**: `media/buildings/{building_id}/plans/`

### Documents Needed:
- **Building License** (`type: 'building_license'`)
  - **Purpose**: Official building license or permit
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional
  - **Public**: No (private)
  - **Expiration**: Yes (licenses may expire)
  - **Storage**: `documents/buildings/{building_id}/licenses/`

- **Building Certificate** (`type: 'building_certificate'`)
  - **Purpose**: Safety or compliance certificates
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional
  - **Public**: No (private)
  - **Expiration**: Yes (certificates may expire)
  - **Storage**: `documents/buildings/{building_id}/certificates/`

---

## 8. Unit Model

### Media Files Needed:
- **Unit Photos** (`type: 'unit_photo'`)
  - **Purpose**: Photos of the unit (interior, exterior, amenities)
  - **File Types**: JPG, PNG
  - **Max Size**: 5MB per image
  - **Required**: No (optional, but recommended)
  - **Public**: Yes (can be displayed in listings)
  - **Storage**: `media/units/{unit_id}/photos/`
  - **Polymorphic**: `entity_type: 'App\Models\V1\Ownership\Unit'`, `entity_id: {unit_id}`
  - **Multiple**: Yes (can have multiple photos)
  - **Order**: Yes (display order matters - first photo is main/cover)
  - **Auto Resize**: Yes (generate thumbnails: 800x600, 400x300, 200x150)

- **Unit Video** (`type: 'unit_video'`)
  - **Purpose**: Video tour of the unit
  - **File Types**: MP4, MOV, AVI
  - **Max Size**: 100MB
  - **Required**: No (optional)
  - **Public**: Yes (can be displayed)
  - **Storage**: `media/units/{unit_id}/videos/`

- **Unit 360 View** (`type: 'unit_360'`)
  - **Purpose**: 360-degree view images
  - **File Types**: JPG, PNG
  - **Max Size**: 10MB per image
  - **Required**: No (optional)
  - **Public**: Yes (can be displayed)
  - **Storage**: `media/units/{unit_id}/360/`

### Documents Needed:
- **Unit Floor Plan** (`type: 'unit_floor_plan'`)
  - **Purpose**: Floor plan of the unit
  - **File Types**: PDF, JPG, PNG
  - **Max Size**: 5MB
  - **Required**: Optional
  - **Public**: Yes (can be displayed)
  - **Expiration**: No
  - **Storage**: `documents/units/{unit_id}/floor_plans/`

---

## 9. Portfolio Model

### Media Files Needed:
- **Portfolio Images** (`type: 'portfolio_image'`)
  - **Purpose**: Images representing the portfolio
  - **File Types**: JPG, PNG
  - **Max Size**: 5MB per image
  - **Required**: No (optional)
  - **Public**: Yes (can be displayed)
  - **Storage**: `media/portfolios/{portfolio_id}/images/`
  - **Polymorphic**: `entity_type: 'App\Models\V1\Ownership\Portfolio'`, `entity_id: {portfolio_id}`
  - **Multiple**: Yes (can have multiple images)
  - **Order**: Yes (display order matters)
  - **Auto Resize**: Yes (generate thumbnails)

### Documents Needed:
- **Portfolio Documents** (`type: 'portfolio_document'`)
  - **Purpose**: General documents related to portfolio
  - **File Types**: PDF, DOC, DOCX, JPG, PNG
  - **Max Size**: 10MB per file
  - **Required**: No (optional)
  - **Public**: No (private)
  - **Expiration**: No
  - **Storage**: `documents/portfolios/{portfolio_id}/`

---

## Summary Table

| Model | Media Types | Document Types | Total Files |
|-------|-------------|----------------|-------------|
| **Ownership** | Logo (1) | Registration, Tax (2) | 3 |
| **User** | Avatar (1) | ID Document (1) | 2 |
| **Tenant** | Profile Photo (1) | National ID, Employment, Income (3) | 4 |
| **Contract** | Contract Images (multiple) | Contract Doc, Signature, Ejar, Deposit (4) | 5+ |
| **Invoice** | None | Invoice PDF, Attachments (2) | 2+ |
| **Payment** | Receipt Image (1) | Receipt, Bank Statement (2) | 3 |
| **Building** | Photos, Plans (2) | License, Certificate (2) | 4+ |
| **Unit** | Photos, Video, 360 (3) | Floor Plan (1) | 4+ |
| **Portfolio** | Images (1) | Documents (1) | 2+ |

---

## Implementation Notes

1. **Polymorphic Relationships**: All media and documents use polymorphic relationships (`entity_type`, `entity_id`) for flexibility.

2. **Storage Structure**: Files are organized by entity type and ID for easy management and cleanup.

3. **Public vs Private**:
   - **Media**: Usually public (for display in listings, profiles)
   - **Documents**: Usually private (sensitive information)

4. **File Size Limits**: Defined in system settings per file type.

5. **Auto Processing**:
   - Images: Auto-resize, generate thumbnails
   - PDFs: Generate previews
   - Videos: Generate thumbnails

6. **Expiration Handling**: Documents with expiration dates should trigger notifications before expiry.

7. **Required Documents**: Some documents may be required based on system settings (e.g., `id_verification_required`).

8. **Multiple Files**: Most entities support multiple files of the same type (e.g., multiple unit photos).

9. **Display Order**: Media files have an `order` field for controlling display sequence.

10. **Cleanup**: When an entity is deleted, associated media and documents should be deleted or archived.

