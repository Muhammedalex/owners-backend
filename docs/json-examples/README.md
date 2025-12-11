# Property Structure JSON Examples

This directory contains comprehensive JSON examples for all property structure models in the Owners Management System.

## Models

### 1. Portfolio (`portfolio-example.json`)
Represents a portfolio (محفظة) - a collection of buildings within an ownership.

**Key Fields:**
- `id`: Unique identifier
- `uuid`: UUID identifier
- `name`: Portfolio name
- `code`: Unique code within ownership
- `type`: Portfolio type (general, residential, commercial, mixed, industrial)
- `area`: Total area in square meters
- `active`: Active status

**Relationships:**
- `ownership`: Parent ownership
- `parent`: Parent portfolio (for nested portfolios)
- `children`: Child portfolios
- `locations`: Portfolio locations (addresses)
- `buildings`: Buildings in this portfolio

### 2. Portfolio Location (`portfolio-location-example.json`)
Represents a location/address for a portfolio.

**Key Fields:**
- `id`: Unique identifier
- `street`: Street address
- `city`: City name
- `state`: State/Province
- `country`: Country (default: Saudi Arabia)
- `zip_code`: Postal code
- `latitude`: Latitude coordinate
- `longitude`: Longitude coordinate
- `primary`: Primary location flag

### 3. Building (`building-example.json`)
Represents a building (مبنى) - contains floors and units.

**Key Fields:**
- `id`: Unique identifier
- `uuid`: UUID identifier
- `name`: Building name
- `code`: Unique code within portfolio
- `type`: Building type (residential, commercial, mixed, office, retail)
- `description`: Building description
- `street`, `city`, `state`, `country`, `zip_code`: Address
- `latitude`, `longitude`: Coordinates
- `floors_count`: Number of floors
- `year`: Construction year
- `active`: Active status

**Relationships:**
- `ownership`: Parent ownership
- `portfolio`: Parent portfolio
- `parent`: Parent building (for nested buildings)
- `children`: Child buildings
- `floors`: Building floors

### 4. Building Floor (`building-floor-example.json`)
Represents a floor (طابق) within a building.

**Key Fields:**
- `id`: Unique identifier
- `number`: Floor number (can be negative for basements, e.g., -1, -2)
- `name`: Floor name (e.g., "Ground Floor", "Floor 2", "Basement 1")
- `description`: Floor description
- `units`: Number of units on this floor
- `active`: Active status

**Relationships:**
- `building`: Parent building

### 5. Unit (`unit-example.json`)
Represents a unit (وحدة) - the smallest property unit.

**Key Fields:**
- `uuid`: UUID identifier
- `number`: Unit number (unique within building)
- `type`: Unit type (apartment, office, shop, warehouse, studio, penthouse)
- `name`: Unit name
- `description`: Unit description
- `area`: Area in square meters
- `price_monthly`: Monthly rental price
- `price_quarterly`: Quarterly rental price
- `price_yearly`: Yearly rental price
- `status`: Unit status (available, rented, maintenance, reserved, sold)
- `active`: Active status

**Relationships:**
- `ownership`: Parent ownership
- `building`: Parent building
- `floor`: Parent floor
- `specifications`: Unit specifications (key-value pairs)

**Additional Unit Examples:**
- `unit-office-example.json`: Office unit with office-specific specifications
- `unit-shop-example.json`: Shop unit with retail-specific specifications
- `unit-warehouse-example.json`: Warehouse unit with storage-specific specifications
- `unit-penthouse-example.json`: Penthouse unit with luxury specifications

### 6. Unit Specification (`unit-specification-example.json`)
Represents a specification (key-value pair) for a unit.

**Key Fields:**
- `id`: Unique identifier
- `key`: Specification key (e.g., "bedrooms", "bathrooms", "parking")
- `value`: Specification value
- `type`: Value type (integer, boolean, string)

**Common Specification Keys:**
- **For Apartments/Studios/Penthouses:**
  - `bedrooms`: Number of bedrooms (integer)
  - `bathrooms`: Number of bathrooms (integer)
  - `balcony`: Has balcony (boolean)
  - `parking`: Number of parking spaces (integer)
  - `furnished`: Is furnished (boolean)

- **For Offices:**
  - `capacity`: Number of people (integer)
  - `meeting_rooms`: Number of meeting rooms (integer)
  - `parking`: Number of parking spaces (integer)
  - `furnished`: Is furnished (boolean)

- **For Shops:**
  - `storefront`: Has storefront (boolean)
  - `parking`: Number of parking spaces (integer)
  - `storage`: Has storage (boolean)

- **For Warehouses:**
  - `loading_dock`: Has loading dock (boolean)
  - `ceiling_height`: Ceiling height in meters (integer)
  - `security`: Has security (boolean)

## Additional Examples

### Nested Structures
- `portfolio-with-nested-example.json`: Portfolio with child portfolios (nested structure)

### API Response Formats
- `api-response-example.json`: Complete API response structure for a single resource
- `api-paginated-response-example.json`: Paginated API response structure for list endpoints

## Usage

These JSON examples can be used for:
1. **API Testing**: Use as request/response examples in Postman or API documentation
2. **Frontend Development**: Reference for data structure when building UI components
3. **Integration**: Understand the data format when integrating with other systems
4. **Documentation**: Include in API documentation or developer guides
5. **Mock Data**: Use as mock data for development and testing

## Notes

- All timestamps are in ISO 8601 format (e.g., `2025-01-15T10:30:00.000000Z`)
- UUIDs are in standard UUID format
- Coordinates are decimal degrees (latitude: -90 to 90, longitude: -180 to 180)
- Prices are in the base currency (SAR - Saudi Riyal)
- Areas are in square meters
- Floor numbers can be negative for basements (e.g., -1 for Basement 1)
- Unit numbers for basement floors use "B" prefix (e.g., "B101" for basement floor -1, unit 1)

## Unit Type Examples

Different unit types have different specifications:

1. **Apartment/Studio/Penthouse** (`unit-example.json`, `unit-penthouse-example.json`):
   - bedrooms, bathrooms, balcony, parking, furnished

2. **Office** (`unit-office-example.json`):
   - capacity, meeting_rooms, parking, furnished

3. **Shop** (`unit-shop-example.json`):
   - storefront, parking, storage

4. **Warehouse** (`unit-warehouse-example.json`):
   - loading_dock, ceiling_height, security

## Hierarchy

```
Ownership
  └── Portfolio
      ├── PortfolioLocation (1-N)
      └── Building (1-N)
          ├── BuildingFloor (1-N)
          └── Unit (1-N)
              └── UnitSpecification (0-N)
```

---

## Phase 1 Modules Examples

### Tenant Example
- **File**: `tenant-example.json`
- **Endpoint**: `GET /api/v1/tenants/{id}`
- **Description**: Complete tenant data with user, ownership, and contracts relationships
- **Relationships Included**:
  - `user`: Full user information (email, phone, name, type, etc.)
  - `ownership`: Ownership details (name, type, address, contact info)
  - `contracts`: List of all contracts for this tenant (with unit, tenant, ownership)

### Contract Example
- **File**: `contract-example.json`
- **Endpoint**: `GET /api/v1/contracts/{uuid}`
- **Description**: Complete contract data with all relationships
- **Relationships Included**:
  - `unit`: Unit details (with building, floor, ownership)
  - `tenant`: Tenant information (with user, ownership)
  - `ownership`: Ownership details
  - `created_by`: User who created the contract
  - `approved_by`: User who approved the contract
  - `parent`: Parent contract (for contract versions)
  - `children`: Child contracts (contract versions/renewals)
  - `terms`: Contract terms (key-value pairs)
  - `invoices`: List of all invoices for this contract (with items, payments)

### Invoice Example
- **File**: `invoice-example.json`
- **Endpoint**: `GET /api/v1/invoices/{uuid}`
- **Description**: Complete invoice data with all relationships
- **Relationships Included**:
  - `contract`: Contract details (with unit, tenant.user, ownership)
  - `ownership`: Ownership details
  - `generated_by`: User who generated the invoice
  - `items`: Invoice items list (type, description, quantity, unit_price, total)
  - `payments`: List of all payments for this invoice (with confirmed_by)
  - `total_paid`: Sum of all paid payments
  - `remaining_amount`: Remaining amount to be paid (total - total_paid)

### Payment Example
- **File**: `payment-example.json`
- **Endpoint**: `GET /api/v1/payments/{uuid}`
- **Description**: Complete payment data with all relationships
- **Relationships Included**:
  - `invoice`: Invoice details (with contract.unit, contract.tenant.user, contract.ownership, items, payments, ownership, generated_by)
  - `ownership`: Ownership details
  - `confirmed_by`: User who confirmed the payment

## Phase 1 Modules Notes

- All examples show the full response structure including nested relationships
- Relationships are loaded using `with()` in the controller's `show()` method
- Some nested relationships may be `null` if not loaded (using `whenLoaded()`)
- All dates are in ISO 8601 format (e.g., `2025-01-15T10:30:00+00:00`)
- All monetary values are in SAR (Saudi Riyal)
- Phone numbers are in international format with country code (+966 for Saudi Arabia)
- Contract `ejar_code` is optional (can be `null` for older/unregistered contracts)
- Payment `transaction_id` is optional (can be `null`)
- Invoice `items_total` is calculated from items when items are loaded
- Invoice `total_paid` and `remaining_amount` are calculated from payments when payments are loaded

