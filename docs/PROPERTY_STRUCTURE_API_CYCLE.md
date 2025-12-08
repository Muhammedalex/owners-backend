# Property Structure API Cycle - Complete Documentation

## Overview

This document provides complete documentation for the Property Structure API cycle, including create, read, update, and delete operations for all models in the hierarchy.

**Property Structure Hierarchy:**
```
Ownership
  └── Portfolio (محفظة)
      ├── PortfolioLocation (1-N)
      └── Building (مبنى) (1-N)
          └── BuildingFloor (طابق) (1-N)
              └── Unit (وحدة) (1-N)
                  └── UnitSpecification (0-N)
```

---

## Important Concepts

### UUID vs ID Usage

| Model | Route Parameter | Request Body References | Notes |
|-------|----------------|------------------------|-------|
| **Portfolio** | `{portfolio:uuid}` | `parent_id` (integer ID) | Use UUID in URL, ID in body |
| **Building** | `{building:uuid}` | `portfolio_id` (integer ID), `parent_id` (integer ID) | Use UUID in URL, ID in body |
| **BuildingFloor** | `{buildingFloor}` (ID) | `building_id` (integer ID) | **Only model using ID in route** |
| **Unit** | `{unit:uuid}` | `building_id` (integer ID), `floor_id` (integer ID) | Use UUID in URL, ID in body |

### Key Rules

1. **Route Parameters:**
   - Portfolio, Building, Unit: Use **UUID** in route parameters
   - BuildingFloor: Use **ID** in route parameters (exception)

2. **Request Body:**
   - All foreign key references use **integer ID** (not UUID)
   - `portfolio_id`, `building_id`, `floor_id`, `parent_id` are all integers

3. **Ownership Scope:**
   - All endpoints require `ownership.scope` middleware
   - Ownership ID comes from `ownership_uuid` cookie (automatically set)
   - **DO NOT** send `ownership_id` in request body - it's set automatically

4. **Authentication:**
   - All endpoints require `auth:sanctum` middleware
   - Include `Authorization: Bearer {token}` header

---

## Base URL

All endpoints are prefixed with:
```
/api/v1/ownerships
```

---

## 1. Portfolio APIs

### 1.1 List Portfolios

**Endpoint:** `GET /api/v1/ownerships/portfolios`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by name, code, or description
- `type` (optional): Filter by portfolio type
- `parent_id` (optional): Filter by parent portfolio ID (use `null` for root portfolios)
- `active` (optional): Filter by active status (true/false)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Main Portfolio",
      "code": "PORT-001-01",
      "type": "residential",
      "description": "Main portfolio",
      "area": 25000.50,
      "active": true,
      "ownership": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Al-Rashid Real Estate Company"
      },
      "parent": null,
      "children": [],
      "locations": [],
      "buildings": [],
      "buildings_count": 3,
      "children_count": 0,
      "created_at": "2025-01-15T10:00:00.000000Z",
      "updated_at": "2025-01-15T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### 1.2 Create Portfolio

**Endpoint:** `POST /api/v1/ownerships/portfolios`

**Request Body:**
```json
{
  "name": "Main Portfolio",
  "code": "PORT-001-01",
  "type": "residential",
  "description": "Main portfolio description",
  "area": 25000.50,
  "parent_id": null,
  "active": true
}
```

**Required Fields:**
- `name` (string, max:255)
- `code` (string, max:50) - Must be unique within ownership

**Optional Fields:**
- `type` (string, max:50) - e.g., "general", "residential", "commercial", "mixed", "industrial"
- `description` (string)
- `area` (numeric, min:0) - Area in square meters
- `parent_id` (integer, nullable) - Parent portfolio ID for nested portfolios
- `active` (boolean, default: true)

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Portfolio created successfully.",
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "name": "Main Portfolio",
    "code": "PORT-001-01",
    "type": "residential",
    "description": "Main portfolio description",
    "area": 25000.50,
    "active": true,
    "ownership": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Al-Rashid Real Estate Company"
    },
    "parent": null,
    "children": [],
    "locations": [],
    "buildings": [],
    "buildings_count": 0,
    "children_count": 0,
    "created_at": "2025-01-15T10:00:00.000000Z",
    "updated_at": "2025-01-15T10:00:00.000000Z"
  }
}
```

### 1.3 Get Portfolio

**Endpoint:** `GET /api/v1/ownerships/portfolios/{portfolio:uuid}`

**Route Parameter:**
- `portfolio`: Portfolio UUID (e.g., `550e8400-e29b-41d4-a716-446655440001`)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "name": "Main Portfolio",
    "code": "PORT-001-01",
    "type": "residential",
    "description": "Main portfolio description",
    "area": 25000.50,
    "active": true,
    "ownership": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Al-Rashid Real Estate Company"
    },
    "parent": null,
    "children": [],
    "locations": [],
    "buildings": [],
    "buildings_count": 3,
    "children_count": 0,
    "created_at": "2025-01-15T10:00:00.000000Z",
    "updated_at": "2025-01-15T10:00:00.000000Z"
  }
}
```

### 1.4 Update Portfolio

**Endpoint:** `PUT /api/v1/ownerships/portfolios/{portfolio:uuid}` or `PATCH /api/v1/ownerships/portfolios/{portfolio:uuid}`

**Route Parameter:**
- `portfolio`: Portfolio UUID

**Request Body (all fields optional with `sometimes` rule):**
```json
{
  "name": "Updated Portfolio Name",
  "code": "PORT-001-01",
  "type": "commercial",
  "description": "Updated description",
  "area": 30000.00,
  "parent_id": null,
  "active": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Portfolio updated successfully.",
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "name": "Updated Portfolio Name",
    "code": "PORT-001-01",
    "type": "commercial",
    "description": "Updated description",
    "area": 30000.00,
    "active": true,
    "ownership": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Al-Rashid Real Estate Company"
    },
    "parent": null,
    "children": [],
    "locations": [],
    "buildings": [],
    "buildings_count": 3,
    "children_count": 0,
    "created_at": "2025-01-15T10:00:00.000000Z",
    "updated_at": "2025-01-15T11:00:00.000000Z"
  }
}
```

### 1.5 Delete Portfolio

**Endpoint:** `DELETE /api/v1/ownerships/portfolios/{portfolio:uuid}`

**Route Parameter:**
- `portfolio`: Portfolio UUID

**Response:**
```json
{
  "success": true,
  "message": "Portfolio deleted successfully."
}
```

### 1.6 Activate/Deactivate Portfolio

**Endpoints:**
- `POST /api/v1/ownerships/portfolios/{portfolio:uuid}/activate`
- `POST /api/v1/ownerships/portfolios/{portfolio:uuid}/deactivate`

**Route Parameter:**
- `portfolio`: Portfolio UUID

**Response:**
```json
{
  "success": true,
  "message": "Portfolio activated successfully.",
  "data": { /* Portfolio object */ }
}
```

---

## 2. Building APIs

### 2.1 List Buildings

**Endpoint:** `GET /api/v1/ownerships/buildings`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by name, code, or description
- `type` (optional): Filter by building type
- `portfolio_id` (optional): Filter by portfolio ID
- `parent_id` (optional): Filter by parent building ID (use `null` for root buildings)
- `city` (optional): Filter by city
- `active` (optional): Filter by active status (true/false)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "660e8400-e29b-41d4-a716-446655440001",
      "name": "Tower A",
      "code": "BLD-001-01",
      "type": "residential",
      "description": "Building description",
      "street": "King Fahd Road, 123",
      "city": "Riyadh",
      "state": "Riyadh Province",
      "country": "Saudi Arabia",
      "zip_code": "12345",
      "latitude": 24.7136,
      "longitude": 46.6753,
      "floors_count": 3,
      "year": 2020,
      "active": true,
      "ownership": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Al-Rashid Real Estate Company"
      },
      "portfolio": {
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "name": "Main Portfolio",
        "code": "PORT-001-01"
      },
      "parent": null,
      "children": [],
      "floors": [],
      "floors_count": 3,
      "children_count": 0,
      "created_at": "2025-01-15T10:30:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### 2.2 Create Building

**Endpoint:** `POST /api/v1/ownerships/buildings`

**Request Body:**
```json
{
  "name": "Tower A",
  "code": "BLD-001-01",
  "type": "residential",
  "description": "Building description",
  "portfolio_id": 1,
  "street": "King Fahd Road, 123",
  "city": "Riyadh",
  "state": "Riyadh Province",
  "country": "Saudi Arabia",
  "zip_code": "12345",
  "latitude": 24.7136,
  "longitude": 46.6753,
  "floors": 3,
  "year": 2020,
  "parent_id": null,
  "active": true
}
```

**Required Fields:**
- `name` (string, max:255)
- `code` (string, max:50) - Must be unique within ownership
- `type` (string, max:50) - e.g., "residential", "commercial", "mixed", "office", "retail"
- `portfolio_id` (integer) - Portfolio ID (must belong to same ownership)

**Optional Fields:**
- `description` (string)
- `street`, `city`, `state`, `country`, `zip_code` (address fields)
- `latitude` (numeric, between:-90,90)
- `longitude` (numeric, between:-180,180)
- `floors` (integer, min:1) - Number of floors
- `year` (integer, min:1800, max:current year + 10) - Construction year
- `parent_id` (integer, nullable) - Parent building ID for nested buildings
- `active` (boolean, default: true)

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Building created successfully.",
  "data": {
    "id": 1,
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "name": "Tower A",
    "code": "BLD-001-01",
    "type": "residential",
    "description": "Building description",
    "street": "King Fahd Road, 123",
    "city": "Riyadh",
    "state": "Riyadh Province",
    "country": "Saudi Arabia",
    "zip_code": "12345",
    "latitude": 24.7136,
    "longitude": 46.6753,
    "floors_count": 3,
    "year": 2020,
    "active": true,
    "ownership": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Al-Rashid Real Estate Company"
    },
    "portfolio": {
      "uuid": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Main Portfolio",
      "code": "PORT-001-01"
    },
    "parent": null,
    "children": [],
    "floors": [],
    "floors_count": 3,
    "children_count": 0,
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

### 2.3 Get Building

**Endpoint:** `GET /api/v1/ownerships/buildings/{building:uuid}`

**Route Parameter:**
- `building`: Building UUID (e.g., `660e8400-e29b-41d4-a716-446655440001`)

**Response:** Same structure as create response

### 2.4 Update Building

**Endpoint:** `PUT /api/v1/ownerships/buildings/{building:uuid}` or `PATCH /api/v1/ownerships/buildings/{building:uuid}`

**Route Parameter:**
- `building`: Building UUID

**Request Body (all fields optional with `sometimes` rule):**
```json
{
  "name": "Updated Building Name",
  "code": "BLD-001-01",
  "type": "commercial",
  "description": "Updated description",
  "portfolio_id": 1,
  "street": "Updated Street",
  "city": "Riyadh",
  "floors": 5,
  "year": 2021,
  "parent_id": null,
  "active": true
}
```

**Response:** Same structure as create response

### 2.5 Delete Building

**Endpoint:** `DELETE /api/v1/ownerships/buildings/{building:uuid}`

**Route Parameter:**
- `building`: Building UUID

**Response:**
```json
{
  "success": true,
  "message": "Building deleted successfully."
}
```

### 2.6 Activate/Deactivate Building

**Endpoints:**
- `POST /api/v1/ownerships/buildings/{building:uuid}/activate`
- `POST /api/v1/ownerships/buildings/{building:uuid}/deactivate`

**Route Parameter:**
- `building`: Building UUID

---

## 3. Building Floor APIs

### 3.1 List Building Floors

**Endpoint:** `GET /api/v1/ownerships/buildings/floors`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `building_id` (optional): Filter by building ID
- `search` (optional): Search by name or description
- `active` (optional): Filter by active status (true/false)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "number": 1,
      "name": "Ground Floor",
      "description": "Floor 1 of Tower A",
      "units": 5,
      "active": true,
      "building": {
        "uuid": "660e8400-e29b-41d4-a716-446655440001",
        "name": "Tower A",
        "code": "BLD-001-01"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### 3.2 Create Building Floor

**Endpoint:** `POST /api/v1/ownerships/buildings/floors`

**Request Body:**
```json
{
  "building_id": 1,
  "number": 1,
  "name": "Ground Floor",
  "description": "Floor 1 of Tower A",
  "units": 5,
  "active": true
}
```

**Required Fields:**
- `building_id` (integer) - Building ID (must belong to same ownership)
- `number` (integer) - Floor number (must be unique within building, can be negative for basements)

**Optional Fields:**
- `name` (string, max:100)
- `description` (string)
- `units` (integer, min:0) - Number of units on this floor
- `active` (boolean, default: true)

**Note:** Floor numbers can be negative for basements (e.g., -1, -2)

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Building floor created successfully.",
  "data": {
    "id": 1,
    "number": 1,
    "name": "Ground Floor",
    "description": "Floor 1 of Tower A",
    "units": 5,
    "active": true,
    "building": {
      "uuid": "660e8400-e29b-41d4-a716-446655440001",
      "name": "Tower A",
      "code": "BLD-001-01"
    }
  }
}
```

### 3.3 Get Building Floor

**Endpoint:** `GET /api/v1/ownerships/buildings/floors/{buildingFloor}`

**Route Parameter:**
- `buildingFloor`: Building Floor **ID** (integer, NOT UUID)

**Example:** `GET /api/v1/ownerships/buildings/floors/1`

**Response:** Same structure as create response

### 3.4 Update Building Floor

**Endpoint:** `PUT /api/v1/ownerships/buildings/floors/{buildingFloor}` or `PATCH /api/v1/ownerships/buildings/floors/{buildingFloor}`

**Route Parameter:**
- `buildingFloor`: Building Floor **ID** (integer)

**Request Body (all fields optional with `sometimes` rule):**
```json
{
  "building_id": 1,
  "number": 1,
  "name": "Updated Floor Name",
  "description": "Updated description",
  "units": 7,
  "active": true
}
```

**Response:** Same structure as create response

### 3.5 Delete Building Floor

**Endpoint:** `DELETE /api/v1/ownerships/buildings/floors/{buildingFloor}`

**Route Parameter:**
- `buildingFloor`: Building Floor **ID** (integer)

**Response:**
```json
{
  "success": true,
  "message": "Building floor deleted successfully."
}
```

### 3.6 Activate/Deactivate Building Floor

**Endpoints:**
- `POST /api/v1/ownerships/buildings/floors/{buildingFloor}/activate`
- `POST /api/v1/ownerships/buildings/floors/{buildingFloor}/deactivate`

**Route Parameter:**
- `buildingFloor`: Building Floor **ID** (integer)

---

## 4. Unit APIs

### 4.1 List Units

**Endpoint:** `GET /api/v1/ownerships/units`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by number, name, or description
- `type` (optional): Filter by unit type (apartment, office, shop, etc.)
- `status` (optional): Filter by status (available, rented, maintenance, reserved, sold)
- `building_id` (optional): Filter by building ID
- `floor_id` (optional): Filter by floor ID
- `active` (optional): Filter by active status (true/false)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "uuid": "770e8400-e29b-41d4-a716-446655440001",
      "number": "0101",
      "type": "apartment",
      "name": "Unit 0101",
      "description": "apartment unit 0101 on floor 1",
      "area": 120.50,
      "price_monthly": 6000.00,
      "price_quarterly": 17100.00,
      "price_yearly": 64800.00,
      "status": "available",
      "active": true,
      "ownership": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Al-Rashid Real Estate Company"
      },
      "building": {
        "uuid": "660e8400-e29b-41d4-a716-446655440001",
        "name": "Tower A",
        "code": "BLD-001-01"
      },
      "floor": {
        "id": 1,
        "number": 1,
        "name": "Ground Floor"
      },
      "specifications": [],
      "created_at": "2025-01-15T10:40:00.000000Z",
      "updated_at": "2025-01-15T10:40:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### 4.2 Create Unit

**Endpoint:** `POST /api/v1/ownerships/units`

**Request Body:**
```json
{
  "building_id": 1,
  "floor_id": 1,
  "number": "0101",
  "type": "apartment",
  "name": "Unit 0101",
  "description": "apartment unit 0101 on floor 1",
  "area": 120.50,
  "price_monthly": 6000.00,
  "price_quarterly": 17100.00,
  "price_yearly": 64800.00,
  "status": "available",
  "active": true,
  "specifications": [
    {
      "key": "bedrooms",
      "value": "2",
      "type": "integer"
    },
    {
      "key": "bathrooms",
      "value": "2",
      "type": "integer"
    },
    {
      "key": "balcony",
      "value": "true",
      "type": "boolean"
    },
    {
      "key": "parking",
      "value": "1",
      "type": "integer"
    }
  ]
}
```

**Required Fields:**
- `building_id` (integer) - Building ID (must belong to same ownership)
- `number` (string, max:50) - Unit number (must be unique within building)
- `type` (string, max:50) - Unit type (apartment, office, shop, warehouse, studio, penthouse)
- `area` (numeric, min:0) - Area in square meters

**Optional Fields:**
- `floor_id` (integer, nullable) - Floor ID (must belong to the building)
- `name` (string, max:255)
- `description` (string)
- `price_monthly` (numeric, min:0)
- `price_quarterly` (numeric, min:0)
- `price_yearly` (numeric, min:0)
- `status` (string) - One of: "available", "rented", "maintenance", "reserved", "sold"
- `active` (boolean, default: true)
- `specifications` (array) - Array of specification objects
  - `key` (string, required) - Specification key
  - `value` (string, nullable) - Specification value
  - `type` (string, nullable) - Value type (integer, boolean, string)

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Unit created successfully.",
  "data": {
    "uuid": "770e8400-e29b-41d4-a716-446655440001",
    "number": "0101",
    "type": "apartment",
    "name": "Unit 0101",
    "description": "apartment unit 0101 on floor 1",
    "area": 120.50,
    "price_monthly": 6000.00,
    "price_quarterly": 17100.00,
    "price_yearly": 64800.00,
    "status": "available",
    "active": true,
    "ownership": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Al-Rashid Real Estate Company"
    },
    "building": {
      "uuid": "660e8400-e29b-41d4-a716-446655440001",
      "name": "Tower A",
      "code": "BLD-001-01"
    },
    "floor": {
      "id": 1,
      "number": 1,
      "name": "Ground Floor"
    },
    "specifications": [
      {
        "id": 1,
        "key": "bedrooms",
        "value": "2",
        "type": "integer"
      },
      {
        "id": 2,
        "key": "bathrooms",
        "value": "2",
        "type": "integer"
      },
      {
        "id": 3,
        "key": "balcony",
        "value": "true",
        "type": "boolean"
      },
      {
        "id": 4,
        "key": "parking",
        "value": "1",
        "type": "integer"
      }
    ],
    "created_at": "2025-01-15T10:40:00.000000Z",
    "updated_at": "2025-01-15T10:40:00.000000Z"
  }
}
```

### 4.3 Get Unit

**Endpoint:** `GET /api/v1/ownerships/units/{unit:uuid}`

**Route Parameter:**
- `unit`: Unit UUID (e.g., `770e8400-e29b-41d4-a716-446655440001`)

**Response:** Same structure as create response

### 4.4 Update Unit

**Endpoint:** `PUT /api/v1/ownerships/units/{unit:uuid}` or `PATCH /api/v1/ownerships/units/{unit:uuid}`

**Route Parameter:**
- `unit`: Unit UUID

**Request Body (all fields optional with `sometimes` rule):**
```json
{
  "building_id": 1,
  "floor_id": 1,
  "number": "0101",
  "type": "apartment",
  "name": "Updated Unit Name",
  "description": "Updated description",
  "area": 125.00,
  "price_monthly": 6500.00,
  "price_quarterly": 18525.00,
  "price_yearly": 70200.00,
  "status": "rented",
  "active": true,
  "specifications": [
    {
      "key": "bedrooms",
      "value": "3",
      "type": "integer"
    },
    {
      "key": "bathrooms",
      "value": "2",
      "type": "integer"
    }
  ]
}
```

**Important Notes:**
- If `specifications` is provided, it will **replace** all existing specifications
- To remove all specifications, send `"specifications": []`
- To keep existing specifications unchanged, omit the `specifications` field

**Response:** Same structure as create response

### 4.5 Delete Unit

**Endpoint:** `DELETE /api/v1/ownerships/units/{unit:uuid}`

**Route Parameter:**
- `unit`: Unit UUID

**Response:**
```json
{
  "success": true,
  "message": "Unit deleted successfully."
}
```

### 4.6 Activate/Deactivate Unit

**Endpoints:**
- `POST /api/v1/ownerships/units/{unit:uuid}/activate`
- `POST /api/v1/ownerships/units/{unit:uuid}/deactivate`

**Route Parameter:**
- `unit`: Unit UUID

---

## Complete Workflow Example

### Creating a Complete Property Structure

**Step 1: Create Portfolio**
```http
POST /api/v1/ownerships/portfolios
Content-Type: application/json

{
  "name": "Main Portfolio",
  "code": "PORT-001-01",
  "type": "residential",
  "area": 25000.50
}
```
**Response:** Portfolio with `id: 1`, `uuid: "550e8400-..."`

**Step 2: Create Building**
```http
POST /api/v1/ownerships/buildings
Content-Type: application/json

{
  "name": "Tower A",
  "code": "BLD-001-01",
  "type": "residential",
  "portfolio_id": 1,
  "floors": 3,
  "year": 2020
}
```
**Response:** Building with `id: 1`, `uuid: "660e8400-..."`

**Step 3: Create Building Floor**
```http
POST /api/v1/ownerships/buildings/floors
Content-Type: application/json

{
  "building_id": 1,
  "number": 1,
  "name": "Ground Floor",
  "units": 5
}
```
**Response:** Floor with `id: 1`

**Step 4: Create Unit**
```http
POST /api/v1/ownerships/units
Content-Type: application/json

{
  "building_id": 1,
  "floor_id": 1,
  "number": "0101",
  "type": "apartment",
  "area": 120.50,
  "price_monthly": 6000.00,
  "status": "available",
  "specifications": [
    {
      "key": "bedrooms",
      "value": "2",
      "type": "integer"
    },
    {
      "key": "bathrooms",
      "value": "2",
      "type": "integer"
    }
  ]
}
```
**Response:** Unit with `uuid: "770e8400-..."`

---

## Common Specification Keys

### For Apartments/Studios/Penthouses:
- `bedrooms` (integer)
- `bathrooms` (integer)
- `balcony` (boolean)
- `parking` (integer)
- `furnished` (boolean)

### For Offices:
- `capacity` (integer)
- `meeting_rooms` (integer)
- `parking` (integer)
- `furnished` (boolean)

### For Shops:
- `storefront` (boolean)
- `parking` (integer)
- `storage` (boolean)

### For Warehouses:
- `loading_dock` (boolean)
- `ceiling_height` (integer)
- `security` (boolean)

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Ownership scope is required."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Portfolio not found or access denied."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "code": [
      "The code has already been taken."
    ],
    "portfolio_id": [
      "The selected portfolio id is invalid."
    ]
  }
}
```

---

## Summary Table

| Operation | Portfolio | Building | BuildingFloor | Unit |
|-----------|-----------|----------|---------------|------|
| **List** | `GET /portfolios` | `GET /buildings` | `GET /buildings/floors` | `GET /units` |
| **Create** | `POST /portfolios` | `POST /buildings` | `POST /buildings/floors` | `POST /units` |
| **Get** | `GET /portfolios/{uuid}` | `GET /buildings/{uuid}` | `GET /buildings/floors/{id}` | `GET /units/{uuid}` |
| **Update** | `PUT/PATCH /portfolios/{uuid}` | `PUT/PATCH /buildings/{uuid}` | `PUT/PATCH /buildings/floors/{id}` | `PUT/PATCH /units/{uuid}` |
| **Delete** | `DELETE /portfolios/{uuid}` | `DELETE /buildings/{uuid}` | `DELETE /buildings/floors/{id}` | `DELETE /units/{uuid}` |
| **Activate** | `POST /portfolios/{uuid}/activate` | `POST /buildings/{uuid}/activate` | `POST /buildings/floors/{id}/activate` | `POST /units/{uuid}/activate` |
| **Deactivate** | `POST /portfolios/{uuid}/deactivate` | `POST /buildings/{uuid}/deactivate` | `POST /buildings/floors/{id}/deactivate` | `POST /units/{uuid}/deactivate` |
| **Route Param** | UUID | UUID | **ID** | UUID |
| **Body References** | `parent_id` (ID) | `portfolio_id`, `parent_id` (ID) | `building_id` (ID) | `building_id`, `floor_id` (ID) |

---

## Important Reminders

1. ✅ **Use UUID in routes** for Portfolio, Building, and Unit
2. ✅ **Use ID in routes** for BuildingFloor (exception)
3. ✅ **Use integer ID in request body** for all foreign key references
4. ✅ **Ownership ID is automatic** - comes from cookie, don't send in body
5. ✅ **All endpoints require authentication** and ownership scope
6. ✅ **Floor numbers can be negative** for basements (-1, -2, etc.)
7. ✅ **Unit specifications are replaced** when updating (not merged)

