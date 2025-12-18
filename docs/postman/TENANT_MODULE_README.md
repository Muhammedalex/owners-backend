# Tenant Module API - Postman Collection & Documentation

## Files Included

1. **Tenant_Module_API.postman_collection.json**
   - Complete Postman collection with all tenant endpoints
   - Includes Login endpoint for authentication
   - Ready to import into Postman

2. **TENANT_MODULE_API_DOCUMENTATION.md**
   - Complete API documentation in English
   - All endpoints with examples
   - Frontend integration examples
   - Error handling guide

3. **TENANT_MODULE_API_DOCUMENTATION_AR.md**
   - Complete API documentation in Arabic
   - نفس المحتوى بالعربي
   - أمثلة تكامل Frontend
   - دليل معالجة الأخطاء

## Quick Start

### 1. Import Postman Collection

1. Open Postman
2. Click **Import** button
3. Select `Tenant_Module_API.postman_collection.json`
4. Collection will be imported with all endpoints

### 2. Set Up Environment Variables

Create a new environment in Postman with:

- `base_url`: Your API base URL (e.g., `http://localhost:8000`)
- `access_token`: Will be automatically set after login

### 3. Run Login First

1. Go to **Authentication > Login**
2. Update email and password in request body
3. Click **Send**
4. Access token will be automatically saved to environment

### 4. Use Other Endpoints

All other endpoints will automatically use the access token from environment.

## Endpoints Included

### Authentication
- ✅ Login

### Tenants
- ✅ List Tenants (with filters and pagination)
- ✅ Get Tenant (by ID)
- ✅ Create Tenant (with file uploads)
- ✅ Update Tenant (with file uploads)
- ✅ Delete Tenant

## Features

- **File Upload Support**: All endpoints support multipart/form-data for image uploads
- **Automatic Token Management**: Login automatically saves access token
- **Cookie Support**: Refresh token and ownership UUID handled via cookies
- **Complete Examples**: All endpoints include example requests and responses
- **Error Handling**: Common error scenarios documented

## File Upload Fields

When creating or updating tenants, you can upload:

- `id_document_image` - ID document image
- `commercial_registration_image` - Commercial registration document image
- `municipality_license_image` - Municipality license image

All images are automatically processed and optimized.

## Notes for Frontend Developers

- Use `FormData` for create/update requests with files
- Always include `credentials: 'include'` in fetch requests
- Access token should be stored in localStorage or state
- Ownership scope is handled automatically via cookies

## Support

For questions or issues:
- Check the documentation files
- Review the Postman collection examples
- Contact the backend development team

---

**Last Updated:** December 18, 2025

