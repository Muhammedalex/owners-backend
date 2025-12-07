# Realistic Data Seeders Documentation

## Overview
This document describes the realistic data seeders that create a complete, production-like dataset for testing and development.

## Seeders Structure

### 1. UserSeeder (`database/seeders/V1/Auth/UserSeeder.php`)

Creates realistic users with proper roles and types.

#### Users Created:
- **1 Super Admin**
  - Email: `admin@owners.com`
  - Password: `password`
  - Role: Super Admin
  - Type: admin

- **3 Owners**
  - `owner1@owners.com` - Ahmed Al-Rashid (Al-Rashid Real Estate)
  - `owner2@owners.com` - Mohammed Al-Noor (Al-Noor Property Management)
  - `owner3@owners.com` - Khalid Al-Madinah (Al-Madinah Investment Group)
  - Password: `password`
  - Role: Owner
  - Type: owner

- **14 Staff Users** (various types)
  - 3 Property Managers
  - 2 Accountants
  - 2 Maintenance Staff
  - 2 Receptionists/Office Staff
  - 1 Legal/Compliance
  - 1 Marketing
  - 1 IT Support
  - 2 Assistant Managers
  - Password: `password`
  - Types: manager, accountant, maintenance, staff, legal, marketing, it, assistant

#### Total: 18 Users

---

### 2. OwnershipSeeder (`database/seeders/V1/Ownership/OwnershipSeeder.php`)

Creates 20 realistic ownerships with proper distribution across cities.

#### Ownerships Created:
- **Riyadh (10 ownerships)**
  - Al-Rashid Real Estate Company
  - Al-Noor Property Management
  - Saudi Real Estate Investment
  - King Fahd Properties
  - Olaya Real Estate Group
  - Al-Wurud Investment
  - Malqa Properties
  - Sulaimaniyah Real Estate
  - Al-Nakheel Properties
  - King Abdullah Real Estate

- **Jeddah (5 ownerships)**
  - Al-Madinah Investment Group
  - Red Sea Properties
  - Corniche Real Estate
  - Al-Balad Properties
  - Al-Hamra Real Estate

- **Dammam/Khobar/Dhahran (5 ownerships)**
  - Eastern Province Properties
  - Khobar Real Estate Group
  - Al-Khobar Towers
  - Dammam Commercial Center
  - Al-Dhahran Properties

#### Features:
- Each ownership has:
  - Unique registration number (CR700...)
  - Unique tax ID (300...)
  - Realistic company names
  - Proper addresses (streets, cities, states)
  - Contact information (email, phone)
  - Created by Super Admin

#### User Mappings:
- First 3 ownerships are mapped to the 3 Owner users (one default per owner)
- Staff users are distributed across ownerships
- Board members are added (Chairman, CEO, Managing Director)

---

### 3. PropertyStructureSeeder (`database/seeders/V1/Ownership/PropertyStructureSeeder.php`)

Creates complete property structure for all ownerships.

#### Structure Per Ownership:

**Portfolios (2-5 per ownership)**
- Each portfolio has:
  - Unique code (PORT-XXX-XX)
  - Realistic names (Main Portfolio, North Portfolio, etc.)
  - Types: general, residential, commercial, mixed, industrial
  - Area: 5,000 - 50,000 sqm
  - 1-2 locations with coordinates

**Buildings (3-8 per portfolio)**
- Each building has:
  - Unique code (BLD-XXX-XX)
  - Realistic names (Tower A, Tower B, Complex A, etc.)
  - Types: residential, commercial, mixed, office, retail
  - 3-15 floors
  - Construction year: 2010-2024
  - Full address with coordinates

**Building Floors (per building)**
- Floor numbers: 1 to building floors count
- Some basements (negative numbers: -1, -2)
- Floor names: "Ground Floor", "Floor 2", "Basement 1", etc.
- 5-20 units per floor

**Units (5-20 per floor)**
- Each unit has:
  - Unique number within building (e.g., "0101", "B101")
  - Types: apartment, office, shop, warehouse, studio, penthouse
  - Realistic area based on type:
    - Apartment: 80-200 sqm
    - Office: 50-300 sqm
    - Shop: 30-150 sqm
    - Warehouse: 100-500 sqm
    - Studio: 40-80 sqm
    - Penthouse: 200-500 sqm
  - Pricing:
    - Monthly, quarterly, yearly prices
    - Based on area and type
    - Quarterly: 5% discount
    - Yearly: 10% discount
  - Status: available, rented, maintenance, reserved, sold
  - Specifications (key-value pairs):
    - Apartments: bedrooms, bathrooms, balcony, parking, furnished
    - Offices: capacity, meeting_rooms, parking, furnished
    - Shops: storefront, parking, storage
    - Warehouses: loading_dock, ceiling_height, security

#### Estimated Totals (for 20 ownerships):
- **Portfolios**: ~60-100 portfolios
- **Buildings**: ~300-800 buildings
- **Floors**: ~1,500-8,000 floors
- **Units**: ~15,000-80,000 units
- **Unit Specifications**: ~75,000-400,000 specifications

---

## Running Seeders

### Full Seed (Recommended)
```bash
php artisan db:seed
```

This runs:
1. AuthModuleSeeder (Permissions → Roles → Users)
2. OwnershipModuleSeeder (Ownerships → Property Structure)
3. NotificationModuleSeeder

### Individual Seeders
```bash
# Users only
php artisan db:seed --class="Database\Seeders\V1\Auth\UserSeeder"

# Ownerships only
php artisan db:seed --class="Database\Seeders\V1\Ownership\OwnershipSeeder"

# Property Structure only (requires ownerships)
php artisan db:seed --class="Database\Seeders\V1\Ownership\PropertyStructureSeeder"
```

---

## Default Credentials

All users use the password: `password`

### Super Admin
- Email: `admin@owners.com`
- Password: `password`

### Owners
- Email: `owner1@owners.com` / `owner2@owners.com` / `owner3@owners.com`
- Password: `password`

### Staff Users
- All staff users: `{type}{number}@owners.com`
- Password: `password`
- Examples:
  - `manager1@owners.com`
  - `accountant1@owners.com`
  - `maintenance1@owners.com`
  - `reception1@owners.com`
  - `legal1@owners.com`
  - `marketing1@owners.com`
  - `it1@owners.com`
  - `assistant1@owners.com`

---

## Data Characteristics

### Realistic Data Features:
1. **Arabic Names**: All user names are Arabic-style names
2. **Saudi Cities**: Ownerships distributed across major Saudi cities
3. **Realistic Codes**: Registration numbers, tax IDs follow Saudi patterns
4. **Proper Hierarchy**: Complete property structure hierarchy
5. **Varied Types**: Different building types, unit types, statuses
6. **Realistic Pricing**: Prices based on area and type
7. **Geographic Data**: Proper coordinates for cities
8. **Specifications**: Unit specifications match unit types

### Data Distribution:
- **Ownerships**: 20 (10 Riyadh, 5 Jeddah, 5 Eastern Province)
- **Users**: 18 (1 Super Admin, 3 Owners, 14 Staff)
- **User Mappings**: Owners mapped to first 3 ownerships, staff distributed
- **Board Members**: Chairman, CEO, Managing Director roles

---

## Notes

- All seeders are idempotent (can be run multiple times safely)
- Existing data is checked before creation
- UUIDs are generated for all entities
- All relationships are properly maintained
- Property structure is created for ALL ownerships automatically

---

## Performance Considerations

Creating full property structure for 20 ownerships may take several minutes due to the large amount of data:
- ~60-100 portfolios
- ~300-800 buildings
- ~1,500-8,000 floors
- ~15,000-80,000 units
- ~75,000-400,000 specifications

For faster testing, you can:
1. Reduce ownerships in OwnershipSeeder
2. Reduce ranges in PropertyStructureSeeder (e.g., fewer portfolios per ownership)

