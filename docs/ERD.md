# Entity Relationship Diagram (ERD)

## Overview
This document describes the database schema for the Ownership Management System.

---

## Tables

### system_settings
System-wide configuration settings.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| key | varchar(255) | NOT NULL, UNIQUE | Setting key identifier |
| value | text | | Setting value |
| type | varchar(50) | | Setting type/category |
| description | text | | Setting description |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `key` (unique)
- `type`

---

### ownerships
Ownership entities (companies, organizations, etc.).

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| uuid | char(36) | NOT NULL, UNIQUE | Unique identifier |
| name | varchar(255) | NOT NULL | Display name |
| legal | varchar(255) | | Legal/registered name |
| type | varchar(50) | NOT NULL | Ownership type |
| ownership_type | varchar(50) | NOT NULL | Category of ownership |
| registration | varchar(100) | UNIQUE | Registration number |
| tax_id | varchar(100) | | Tax identification number |
| street | varchar(255) | | Street address |
| city | varchar(100) | NOT NULL | City |
| state | varchar(100) | | State/Province |
| country | varchar(100) | DEFAULT 'Saudi Arabia' | Country |
| zip_code | varchar(20) | | Postal/ZIP code |
| email | varchar(255) | | Contact email |
| phone | varchar(20) | | Contact phone |
| logo | varchar(255) | | Logo file path |
| active | boolean | DEFAULT true | Active status |
| created_by | bigint | | User who created this record |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `uuid` (unique)
- `type`
- `ownership_type`
- `registration` (unique)
- `active`
- `city`

---

### ownership_board_members
Board members associated with ownerships.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| user_id | bigint | FK → users.id | User reference |
| role | varchar(50) | NOT NULL | Board member role |
| active | boolean | DEFAULT true | Active status |
| start_date | date | | Role start date |
| end_date | date | | Role end date |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `ownership_id`
- `user_id`
- `role`
- `active`
- (`ownership_id`, `user_id`) (unique)

---

### users
System users (owners, tenants, admins, etc.).

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| uuid | char(36) | NOT NULL, UNIQUE | Unique identifier |
| type | varchar(50) | NOT NULL | User type |
| email | varchar(255) | NOT NULL, UNIQUE | Email address |
| phone | varchar(20) | | Phone number |
| phone_verified_at | timestamp | | Phone verification timestamp |
| password | varchar(255) | NOT NULL | Hashed password |
| first | varchar(100) | | First name |
| last | varchar(100) | | Last name |
| company | varchar(255) | | Company name |
| avatar | varchar(255) | | Avatar file path |
| email_verified_at | timestamp | | Email verification timestamp |
| active | boolean | DEFAULT true | Active status |
| last_login_at | timestamp | | Last login timestamp |
| attempts | int | DEFAULT 0 | Login attempts counter |
| timezone | varchar(50) | DEFAULT 'Asia/Riyadh' | User timezone |
| locale | varchar(10) | DEFAULT 'ar' | User locale |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `uuid` (unique)
- `email` (unique)
- `type`
- `active`
- `phone`
- `last_login_at`

---

### user_ownership_mapping
Mapping between users and ownerships.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| user_id | bigint | FK → users.id | User reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| default | boolean | DEFAULT false | Default ownership flag |
| created_at | timestamp | | Creation timestamp |

**Indexes:**
- `user_id`
- `ownership_id`
- (`user_id`, `ownership_id`) (unique)
- `default`

---

### portfolios
Property portfolios.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| parent_id | bigint | FK → portfolios.id | Parent portfolio reference |
| name | varchar(255) | NOT NULL | Portfolio name |
| code | varchar(50) | NOT NULL, UNIQUE | Portfolio code |
| type | varchar(50) | DEFAULT 'general' | Portfolio type |
| description | text | | Description |
| area | decimal(12,2) | | Total area |
| active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `ownership_id`
- `parent_id`
- `code` (unique)
- `type`
- `active`

---

### portfolio_locations
Location information for portfolios.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| portfolio_id | bigint | FK → portfolios.id | Portfolio reference |
| street | varchar(255) | | Street address |
| city | varchar(100) | | City |
| state | varchar(100) | | State/Province |
| country | varchar(100) | DEFAULT 'Saudi Arabia' | Country |
| zip_code | varchar(20) | | Postal/ZIP code |
| latitude | decimal(10, 8) | | Latitude coordinate |
| longitude | decimal(11, 8) | | Longitude coordinate |
| primary | boolean | DEFAULT false | Primary location flag |

**Indexes:**
- `portfolio_id`
- `city`
- `primary`
- (`portfolio_id`, `primary`) (unique)

---

### buildings
Building structures.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| portfolio_id | bigint | FK → portfolios.id | Portfolio reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| parent_id | bigint | FK → buildings.id | Parent building reference |
| name | varchar(255) | NOT NULL | Building name |
| code | varchar(50) | NOT NULL | Building code |
| type | varchar(50) | NOT NULL | Building type |
| description | text | | Description |
| street | varchar(255) | | Street address |
| city | varchar(100) | | City |
| state | varchar(100) | | State/Province |
| country | varchar(100) | DEFAULT 'Saudi Arabia' | Country |
| zip_code | varchar(20) | | Postal/ZIP code |
| latitude | decimal(10, 8) | | Latitude coordinate |
| longitude | decimal(11, 8) | | Longitude coordinate |
| floors | int | DEFAULT 1 | Total number of floors |
| year | int | | Construction year |
| active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `portfolio_id`
- `ownership_id`
- `parent_id`
- `code`
- `type`
- `active`
- `city`

---

### building_floors
Building floor information.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| building_id | bigint | FK → buildings.id | Building reference |
| number | int | NOT NULL | Floor number |
| name | varchar(100) | | Floor name |
| description | text | | Description |
| units | int | DEFAULT 0 | Total units on floor |
| active | boolean | DEFAULT true | Active status |

**Indexes:**
- `building_id`
- `number`
- (`building_id`, `number`) (unique)
- `active`

---

### units
Rental/lease units.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| building_id | bigint | FK → buildings.id | Building reference |
| floor_id | bigint | FK → building_floors.id | Floor reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| number | varchar(50) | NOT NULL | Unit number |
| type | varchar(50) | NOT NULL | Unit type |
| name | varchar(255) | | Unit name |
| description | text | | Description |
| area | decimal(8,2) | NOT NULL | Unit area |
| price_monthly | decimal(12,2) | | Monthly price |
| price_quarterly | decimal(12,2) | | Quarterly price |
| price_yearly | decimal(12,2) | | Yearly price |
| status | varchar(50) | DEFAULT 'available' | Unit status |
| active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `building_id`
- `floor_id`
- `ownership_id`
- `number`
- `type`
- `status`
- `active`
- (`building_id`, `number`) (unique)

---

### unit_specifications
Additional specifications for units.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| unit_id | bigint | FK → units.id | Unit reference |
| key | varchar(255) | NOT NULL | Specification key |
| value | text | | Specification value |
| type | varchar(50) | | Value type |

**Indexes:**
- `unit_id`
- `key`
- (`unit_id`, `key`) (unique)

---

### media_files
Media files associated with entities.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| entity_type | varchar(50) | NOT NULL | Related entity type |
| entity_id | bigint | NOT NULL | Related entity ID |
| type | varchar(50) | NOT NULL | File type |
| path | varchar(500) | NOT NULL | File path |
| name | varchar(255) | | File name |
| size | bigint | | File size in bytes |
| mime | varchar(100) | | MIME type |
| title | varchar(255) | | Media title |
| description | text | | Description |
| order | int | DEFAULT 0 | Display order |
| uploaded_by | bigint | FK → users.id | Uploader user reference |
| public | boolean | DEFAULT true | Public visibility flag |
| created_at | timestamp | | Creation timestamp |

**Indexes:**
- `ownership_id`
- `entity_type`
- `entity_id`
- `type`
- `uploaded_by`
- `public`
- `order`
- (`entity_type`, `entity_id`, `type`)

---

### tenants
Tenant information.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| user_id | bigint | FK → users.id, UNIQUE | User reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| national_id | varchar(50) | | National ID number |
| id_type | varchar(50) | DEFAULT 'national_id' | ID type |
| id_document | varchar(255) | | ID document path |
| id_expiry | date | | ID expiry date |
| emergency_name | varchar(100) | | Emergency contact name |
| emergency_phone | varchar(20) | | Emergency contact phone |
| emergency_relation | varchar(50) | | Emergency contact relation |
| employment | varchar(50) | | Employment status |
| employer | varchar(255) | | Employer name |
| income | decimal(12,2) | | Monthly income |
| rating | varchar(50) | DEFAULT 'good' | Credit rating |
| notes | text | | Additional notes |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `user_id` (unique)
- `ownership_id`
- `national_id`
- `id_type`
- `rating`

---

### contracts
Rental contracts.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| unit_id | bigint | FK → units.id | Unit reference |
| tenant_id | bigint | FK → tenants.id | Tenant reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| number | varchar(100) | NOT NULL, UNIQUE | Contract number |
| version | int | DEFAULT 1 | Contract version |
| parent_id | bigint | FK → contracts.id | Parent contract reference |
| start | date | NOT NULL | Contract start date |
| end | date | NOT NULL | Contract end date |
| rent | decimal(12,2) | NOT NULL | Monthly rent amount |
| payment_frequency | varchar(50) | DEFAULT 'monthly' | Payment frequency |
| deposit | decimal(12,2) | | Security deposit amount |
| deposit_status | varchar(50) | DEFAULT 'pending' | Deposit status |
| document | varchar(255) | | Contract document path |
| signature | text | | Digital signature data |
| status | varchar(50) | DEFAULT 'draft' | Contract status |
| created_by | bigint | FK → users.id | Creator user reference |
| approved_by | bigint | FK → users.id | Approver user reference |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `unit_id`
- `tenant_id`
- `ownership_id`
- `number` (unique)
- `status`
- `start`
- `end`
- (`unit_id`, `status`)

---

### contract_terms
Additional terms for contracts.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| contract_id | bigint | FK → contracts.id | Contract reference |
| key | varchar(255) | NOT NULL | Term key |
| value | text | | Term value |
| type | varchar(50) | | Value type |

**Indexes:**
- `contract_id`
- `key`
- (`contract_id`, `key`) (unique)

---

### invoices
Rental invoices.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| contract_id | bigint | FK → contracts.id | Contract reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| number | varchar(100) | NOT NULL, UNIQUE | Invoice number |
| period_start | date | NOT NULL | Billing period start |
| period_end | date | NOT NULL | Billing period end |
| due | date | NOT NULL | Payment due date |
| amount | decimal(12,2) | NOT NULL | Invoice amount |
| tax | decimal(12,2) | DEFAULT 0 | Tax amount |
| tax_rate | decimal(5,2) | DEFAULT 15.00 | Tax rate percentage |
| total | decimal(12,2) | NOT NULL | Total amount including tax |
| status | varchar(50) | DEFAULT 'draft' | Invoice status |
| notes | text | | Additional notes |
| generated_by | bigint | FK → users.id | Generator user reference |
| generated_at | timestamp | | Generation timestamp |
| paid_at | timestamp | | Payment timestamp |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `contract_id`
- `ownership_id`
- `number` (unique)
- `status`
- `due`
- `period_start`
- `period_end`

---

### invoice_items
Line items for invoices.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| invoice_id | bigint | FK → invoices.id | Invoice reference |
| type | varchar(50) | NOT NULL | Item type |
| description | varchar(255) | NOT NULL | Item description |
| quantity | int | DEFAULT 1 | Item quantity |
| unit_price | decimal(10,2) | NOT NULL | Unit price |
| total | decimal(10,2) | NOT NULL | Total price |

**Indexes:**
- `invoice_id`
- `type`

---

### payments
Payment transactions.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| invoice_id | bigint | FK → invoices.id | Invoice reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| method | varchar(50) | NOT NULL | Payment method |
| transaction_id | varchar(255) | UNIQUE | Transaction identifier |
| amount | decimal(12,2) | NOT NULL | Payment amount |
| currency | varchar(3) | DEFAULT 'SAR' | Currency code |
| status | varchar(50) | DEFAULT 'pending' | Payment status |
| gateway_name | varchar(100) | | Payment gateway name |
| gateway_transaction_ref | varchar(255) | | Gateway transaction reference |
| paid_at | timestamp | | Payment timestamp |
| confirmed_by | bigint | FK → users.id | Confirmer user reference |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `invoice_id`
- `ownership_id`
- `transaction_id` (unique)
- `status`
- `paid_at`
- `method`

---

### maintenance_categories
Maintenance request categories.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| name | varchar(100) | NOT NULL | Category name |
| description | text | | Description |
| urgency | varchar(50) | DEFAULT 'medium' | Urgency level |
| response_hours | int | | Expected response time in hours |
| charge | decimal(8,2) | DEFAULT 0 | Service charge |
| active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `ownership_id`
- `urgency`
- `active`

---

### maintenance_requests
Maintenance service requests.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| unit_id | bigint | FK → units.id | Unit reference |
| tenant_id | bigint | FK → tenants.id | Tenant reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| category_id | bigint | FK → maintenance_categories.id | Category reference |
| title | varchar(255) | NOT NULL | Request title |
| description | text | | Request description |
| status | varchar(50) | DEFAULT 'pending' | Request status |
| priority | varchar(50) | DEFAULT 'medium' | Priority level |
| estimated_cost | decimal(10,2) | | Estimated cost |
| actual_cost | decimal(10,2) | | Actual cost |
| requested_at | timestamp | | Request timestamp |
| completed_at | timestamp | | Completion timestamp |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `unit_id`
- `tenant_id`
- `ownership_id`
- `category_id`
- `status`
- `priority`
- `requested_at`

---

### technicians
Maintenance technicians.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| name | varchar(255) | NOT NULL | Technician name |
| email | varchar(255) | | Email address |
| phone | varchar(20) | | Phone number |
| company | varchar(255) | | Company name |
| rate | decimal(8,2) | | Hourly rate |
| active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `ownership_id`
- `email`
- `active`

---

### maintenance_assignments
Assignment of technicians to maintenance requests.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| request_id | bigint | FK → maintenance_requests.id, UNIQUE | Request reference |
| technician_id | bigint | FK → technicians.id | Technician reference |
| assigned_at | timestamp | | Assignment timestamp |
| estimated_cost | decimal(8,2) | | Estimated cost |
| actual_cost | decimal(8,2) | | Actual cost |
| notes | text | | Assignment notes |
| completed_at | timestamp | | Completion timestamp |

**Indexes:**
- `request_id` (unique)
- `technician_id`
- `assigned_at`

---

### facilities
Facilities available for booking.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| portfolio_id | bigint | FK → portfolios.id | Portfolio reference |
| building_id | bigint | FK → buildings.id | Building reference |
| floor_id | bigint | FK → building_floors.id | Floor reference |
| name | varchar(255) | NOT NULL | Facility name |
| type | varchar(50) | NOT NULL | Facility type |
| description | text | | Description |
| location | varchar(255) | | Location description |
| capacity | int | | Maximum capacity |
| public | boolean | DEFAULT true | Public access flag |
| active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `ownership_id`
- `portfolio_id`
- `building_id`
- `floor_id`
- `type`
- `active`
- `public`

---

### facility_operating_hours
Operating hours for facilities.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| facility_id | bigint | FK → facilities.id | Facility reference |
| day | int | NOT NULL | Day of week (0-6) |
| open | time | | Opening time |
| close | time | | Closing time |
| closed | boolean | DEFAULT false | Closed flag |

**Indexes:**
- `facility_id`
- `day`
- (`facility_id`, `day`) (unique)

---

### facility_bookings
Facility booking reservations.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| facility_id | bigint | FK → facilities.id | Facility reference |
| tenant_id | bigint | FK → tenants.id | Tenant reference |
| date | date | NOT NULL | Booking date |
| start | time | NOT NULL | Start time |
| end | time | NOT NULL | End time |
| purpose | text | | Booking purpose |
| status | varchar(50) | DEFAULT 'pending' | Booking status |
| fee | decimal(8,2) | DEFAULT 0 | Booking fee |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

**Indexes:**
- `facility_id`
- `tenant_id`
- `date`
- `status`

---

### system_notifications
System notifications for users.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| user_id | bigint | FK → users.id | User reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| type | varchar(50) | NOT NULL | Notification type |
| title | varchar(255) | NOT NULL | Notification title |
| message | text | | Notification message |
| action_url | varchar(500) | | Action URL |
| entity_type | varchar(50) | | Related entity type |
| entity_id | bigint | | Related entity ID |
| read | boolean | DEFAULT false | Read status |
| urgent | boolean | DEFAULT false | Urgent flag |
| expires_at | timestamp | | Expiration timestamp |
| created_at | timestamp | | Creation timestamp |

**Indexes:**
- `user_id`
- `ownership_id`
- `type`
- `read`
- `urgent`
- `created_at`
- `expires_at`

---

### audit_logs
System audit logs.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| user_id | bigint | FK → users.id | User reference |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| action | varchar(100) | NOT NULL | Action performed |
| model_type | varchar(100) | | Model type |
| model_id | bigint | | Model ID |
| ip_address | varchar(45) | | IP address |
| user_agent | text | | User agent string |
| created_at | timestamp | | Creation timestamp |

**Indexes:**
- `user_id`
- `ownership_id`
- `action`
- `model_type`
- `created_at`

---

### audit_log_details
Detailed changes in audit logs.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| audit_log_id | bigint | FK → audit_logs.id | Audit log reference |
| field_name | varchar(255) | NOT NULL | Changed field name |
| old_value | text | | Old value |
| new_value | text | | New value |

**Indexes:**
- `audit_log_id`
- `field_name`

---

### documents
Document storage.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, Auto Increment | Primary key |
| ownership_id | bigint | FK → ownerships.id | Ownership reference |
| type | varchar(50) | NOT NULL | Document type |
| title | varchar(255) | NOT NULL | Document title |
| description | text | | Description |
| path | varchar(500) | NOT NULL | File path |
| size | bigint | | File size in bytes |
| mime | varchar(100) | | MIME type |
| entity_type | varchar(50) | | Related entity type |
| entity_id | bigint | | Related entity ID |
| uploaded_by | bigint | FK → users.id | Uploader user reference |
| public | boolean | DEFAULT false | Public visibility flag |
| expires_at | timestamp | | Expiration timestamp |
| created_at | timestamp | | Creation timestamp |

**Indexes:**
- `ownership_id`
- `type`
- `entity_type`
- `uploaded_by`
- `public`
- `expires_at`

---

## Relationships

### Primary Relationships
- **ownerships** → **users** (created_by)
- **ownership_board_members** → **ownerships** (ownership_id)
- **ownership_board_members** → **users** (user_id)
- **user_ownership_mapping** → **users** (user_id)
- **user_ownership_mapping** → **ownerships** (ownership_id)
- **portfolios** → **ownerships** (ownership_id)
- **portfolios** → **portfolios** (parent_id) - self-referencing
- **portfolio_locations** → **portfolios** (portfolio_id)
- **buildings** → **portfolios** (portfolio_id)
- **buildings** → **ownerships** (ownership_id)
- **buildings** → **buildings** (parent_id) - self-referencing
- **building_floors** → **buildings** (building_id)
- **units** → **buildings** (building_id)
- **units** → **building_floors** (floor_id)
- **units** → **ownerships** (ownership_id)
- **unit_specifications** → **units** (unit_id)
- **tenants** → **users** (user_id)
- **tenants** → **ownerships** (ownership_id)
- **contracts** → **units** (unit_id)
- **contracts** → **tenants** (tenant_id)
- **contracts** → **ownerships** (ownership_id)
- **contracts** → **users** (created_by, approved_by)
- **contracts** → **contracts** (parent_id) - self-referencing
- **contract_terms** → **contracts** (contract_id)
- **invoices** → **contracts** (contract_id)
- **invoices** → **ownerships** (ownership_id)
- **invoices** → **users** (generated_by)
- **invoice_items** → **invoices** (invoice_id)
- **payments** → **invoices** (invoice_id)
- **payments** → **ownerships** (ownership_id)
- **payments** → **users** (confirmed_by)
- **maintenance_categories** → **ownerships** (ownership_id)
- **maintenance_requests** → **units** (unit_id)
- **maintenance_requests** → **tenants** (tenant_id)
- **maintenance_requests** → **ownerships** (ownership_id)
- **maintenance_requests** → **maintenance_categories** (category_id)
- **technicians** → **ownerships** (ownership_id)
- **maintenance_assignments** → **maintenance_requests** (request_id)
- **maintenance_assignments** → **technicians** (technician_id)
- **facilities** → **ownerships** (ownership_id)
- **facilities** → **portfolios** (portfolio_id)
- **facilities** → **buildings** (building_id)
- **facilities** → **building_floors** (floor_id)
- **facility_operating_hours** → **facilities** (facility_id)
- **facility_bookings** → **facilities** (facility_id)
- **facility_bookings** → **tenants** (tenant_id)
- **system_notifications** → **users** (user_id)
- **system_notifications** → **ownerships** (ownership_id)
- **audit_logs** → **users** (user_id)
- **audit_logs** → **ownerships** (ownership_id)
- **audit_log_details** → **audit_logs** (audit_log_id)
- **documents** → **ownerships** (ownership_id)
- **documents** → **users** (uploaded_by)
- **media_files** → **ownerships** (ownership_id)
- **media_files** → **users** (uploaded_by)

---

## Notes

- All tables include `created_at` and `updated_at` timestamps (except junction tables that may only have `created_at`)
- UUID fields are used for external references and API responses
- Boolean fields use `active`, `public`, `read`, `urgent`, `closed`, `primary`, `default` naming convention
- Foreign key relationships maintain referential integrity
- Composite unique indexes prevent duplicate relationships
- All monetary values use `decimal` type for precision
- Geographic coordinates use `decimal` with appropriate precision
- Status fields use varchar(50) to allow for future status additions
