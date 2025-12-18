# Tenant Invitation Feature - Complete Documentation

## Overview

This folder contains comprehensive documentation for the **Tenant Self-Registration via Invitation** feature. The documentation is organized into separate files for easy navigation and reference.

---

## Documentation Structure

### 📋 Core Documentation

1. **[01-overview.md](./01-overview.md)** - Feature overview, business requirements, and key concepts
2. **[02-database-schema.md](./02-database-schema.md)** - Database structure, migrations, and relationships
3. **[03-api-endpoints-owner.md](./03-api-endpoints-owner.md)** - Owner-facing API endpoints (authenticated)
4. **[04-api-endpoints-public.md](./04-api-endpoints-public.md)** - Public API endpoints (no authentication)
5. **[05-workflow-owner.md](./05-workflow-owner.md)** - Owner workflow: creating and managing invitations
6. **[06-workflow-tenant.md](./06-workflow-tenant.md)** - Tenant workflow: receiving and accepting invitations
7. **[07-invitation-types.md](./07-invitation-types.md)** - Single-use vs Multi-use invitations explained
8. **[08-mail-configuration.md](./08-mail-configuration.md)** - Ownership-specific SMTP mail configuration
9. **[09-permissions-security.md](./09-permissions-security.md)** - Permissions, policies, and security considerations
10. **[10-user-registration-flow.md](./10-user-registration-flow.md)** - User creation, role assignment, and ownership mapping
11. **[11-testing-guide.md](./11-testing-guide.md)** - Testing scenarios, commands, and examples
12. **[12-troubleshooting.md](./12-troubleshooting.md)** - Common issues, errors, and solutions

---

## Quick Start

### For Developers
1. Start with **[01-overview.md](./01-overview.md)** to understand the feature
2. Review **[02-database-schema.md](./02-database-schema.md)** for database structure
3. Check **[03-api-endpoints-owner.md](./03-api-endpoints-owner.md)** and **[04-api-endpoints-public.md](./04-api-endpoints-public.md)** for API reference
4. Read **[11-testing-guide.md](./11-testing-guide.md)** for testing instructions

### For Product Owners
1. Read **[01-overview.md](./01-overview.md)** for business requirements
2. Review **[05-workflow-owner.md](./05-workflow-owner.md)** and **[06-workflow-tenant.md](./06-workflow-tenant.md)** for user flows
3. Check **[07-invitation-types.md](./07-invitation-types.md)** for invitation types

### For System Administrators
1. Review **[08-mail-configuration.md](./08-mail-configuration.md)** for SMTP setup
2. Check **[09-permissions-security.md](./09-permissions-security.md)** for security configuration
3. Read **[12-troubleshooting.md](./12-troubleshooting.md)** for common issues

---

## Key Features

✅ **Single-use Invitations** - Invitations with email/phone (one-time use)  
✅ **Multi-use Invitations** - Invitations without email/phone (multiple tenants can join)  
✅ **Ownership-specific Mail** - Each ownership can have its own SMTP configuration  
✅ **Automatic User Creation** - Users are created with tenant type and role  
✅ **Ownership Mapping** - Users are automatically linked to ownership  
✅ **Secure Tokens** - 64-character random tokens for invitation links  
✅ **Expiration Management** - Configurable expiration (default: 7 days)  
✅ **Bulk Invitations** - Send multiple invitations at once  

---

## Related Documentation

- **Public Documentation:** `docs/new-features/01-tenant-self-registration.md`
- **Postman Collection:** `docs/postman/Tenant_Invitations_API.postman_collection.json`
- **Testing Guide:** `docs/new-features/TESTING_GUIDE.md`
- **Mail Configuration:** `docs/new-features/OWNERSHIP_MAIL_CONFIG.md`

---

## Version

**Version:** 1.0  
**Last Updated:** December 15, 2025  
**Status:** ✅ Implemented and Tested

