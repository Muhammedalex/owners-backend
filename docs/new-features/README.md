# New Features - Study Cases & Workflows

This directory contains comprehensive study cases, workflows, and implementation guides for new features requested by the client.

## Features Overview

### 1. [Tenant Self-Registration via Invitation Link](./01-tenant-self-registration.md)
**Status:** 📋 Study Case Complete

Allows tenants to register themselves using a secure invitation link sent by the ownership owner. This eliminates manual tenant creation and streamlines the onboarding process.

**Key Features:**
- Secure invitation token system
- Email invitation with registration link
- Self-service tenant profile completion
- Automatic user account creation
- Ownership assignment

---

### 2. [Multiple Units per Contract](./02-multiple-units-contract.md)
**Status:** 📋 Study Case Complete

Enables a single tenant to rent multiple units under one contract. This is common for businesses or individuals who need multiple spaces.

**Key Features:**
- Contract-to-units many-to-many relationship
- Single rent amount or per-unit pricing
- Unified contract management
- Invoice generation for multiple units

---

### 3. [Automated Invoice Generation & Reminders](./03-automated-invoices-reminders.md)
**Status:** 📋 Study Case Complete

Automatically generates invoices based on contract payment frequency and sends reminders to tenants and payment collectors. Includes flexible notification system (email, real-time, future SMS).

**Key Features:**
- Automated invoice generation based on contract schedule
- Configurable reminder system (email, real-time notifications, SMS-ready)
- Payment collector role assignment
- Ownership-level notification preferences
- Multi-channel notification service

---

## Implementation Priority

1. **Phase 1:** Tenant Self-Registration (High Priority - User Experience)
2. **Phase 2:** Multiple Units per Contract (Medium Priority - Business Logic)
3. **Phase 3:** Automated Invoices & Reminders (High Priority - Automation)

---

## Common Architecture Patterns

All features follow the existing architecture:
- **Clean Architecture:** Controller → Service → Repository → Model
- **Ownership Scoping:** All data scoped by `ownership_id`
- **UUID Usage:** External references use UUID
- **Permission-Based:** Spatie permissions for authorization
- **Localization:** Arabic/English support

---

## Related Documentation

- [Architecture Guide](../ARCHITECTURE.md)
- [Ownership Workflow](../modules/ownership-workflow.md)
- [Contracts Module](../modules/contracts-module.md)
- [Invoices Module](../modules/invoices-module.md)
- [Tenants Module](../modules/tenants-module.md)

