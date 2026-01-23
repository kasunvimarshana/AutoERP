# AutoERP - Swagger/OpenAPI API Documentation

## Overview

This document provides comprehensive information about the AutoERP API documentation powered by Swagger/OpenAPI 3.0.

## Accessing the API Documentation

### Swagger UI

The interactive API documentation is available at:

**Development:** `http://localhost:8000/api/documentation`
**Production:** `https://api.autoerp.com/api/documentation`

### API Documentation JSON

The OpenAPI specification file is available at:

**Development:** `http://localhost:8000/docs/api-docs.json`
**Production:** `https://api.autoerp.com/docs/api-docs.json`

## Quick Start

### 1. Setup Backend

```bash
cd backend
composer install
php artisan l5-swagger:generate
php artisan serve
```

### 2. Access Swagger UI

Open your browser and navigate to: `http://localhost:8000/api/documentation`

### 3. Authenticate

1. Click the "Authorize" button in the top-right
2. Login via POST `/api/v1/auth/login` to get a token
3. Enter the token in the format: `Bearer YOUR_TOKEN_HERE`
4. Click "Authorize"
5. All subsequent requests will include authentication

## API Documentation Structure

### Base URL

- **Development:** `http://localhost:8000/api/v1`
- **Production:** `https://api.autoerp.com/api/v1`

### Authentication

All protected endpoints require Bearer token authentication:

```http
Authorization: Bearer YOUR_TOKEN_HERE
```

### Standard Response Format

#### Success Response (200, 201)
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { }
}
```

#### Error Response (400, 404, 500)
```json
{
  "success": false,
  "message": "An error occurred",
  "errors": { }
}
```

#### Validation Error Response (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

## Documented Modules

### ✅ Authentication (8 endpoints)
- **POST** `/auth/register` - Register new user
- **POST** `/auth/login` - Login and get token
- **POST** `/auth/logout` - Logout and revoke token
- **GET** `/auth/me` - Get current user profile
- **POST** `/auth/refresh-token` - Refresh authentication token
- **POST** `/auth/password/change` - Change password
- **POST** `/auth/password/request-reset` - Request password reset
- **POST** `/auth/password/reset` - Reset password with token

### ✅ Customer Management (6 endpoints)
- **GET** `/customers` - List all customers (with search/filters)
- **POST** `/customers` - Create new customer
- **GET** `/customers/{id}` - Get customer details
- **PUT** `/customers/{id}` - Update customer
- **DELETE** `/customers/{id}` - Delete customer
- **GET** `/customers/upcoming-services` - Get customers with upcoming services

### ✅ Vehicle Management (6 endpoints)
- **GET** `/vehicles` - List all vehicles (with filters)
- **POST** `/vehicles` - Register new vehicle
- **GET** `/vehicles/{id}` - Get vehicle details
- **PUT** `/vehicles/{id}` - Update vehicle
- **POST** `/vehicles/{id}/transfer-ownership` - Transfer vehicle ownership
- **POST** `/vehicles/{id}/update-mileage` - Update vehicle mileage

### 📋 Additional Modules (To Be Documented)
- Tenant Management
- User Management
- Role & Permission Management
- Appointment Management
- Job Card Management
- Inventory Management
- Invoicing & Payments
- CRM & Communications
- Fleet Management

## Available Schemas

The API documentation includes the following reusable schemas:

- **PaginationMeta** - Standard pagination metadata
- **SuccessResponse** - Standard success response format
- **ErrorResponse** - Standard error response format
- **ValidationErrorResponse** - Validation error format
- **Customer** - Customer entity schema
- **Vehicle** - Vehicle entity schema
- **JobCard** - Job card entity schema
- **Invoice** - Invoice entity schema

## Features

### 🔐 Security
- Bearer token authentication (Laravel Sanctum)
- Role-Based Access Control (RBAC)
- Tenant isolation at database level
- Complete audit trails

### 📊 Pagination
All list endpoints support pagination with the following parameters:
- `per_page` - Results per page (default: 15, max: 100)
- Response includes: `current_page`, `per_page`, `total`, `last_page`

### 🔍 Search & Filtering
Most list endpoints support:
- `search` - Full-text search across relevant fields
- `status` - Filter by status
- Additional entity-specific filters

### 🌐 Multi-Tenancy
- All requests are automatically scoped to the authenticated user's tenant
- Tenant ID is derived from the authenticated user

## Generating Documentation

### Manual Generation

To regenerate the API documentation after making changes:

```bash
cd backend
php artisan l5-swagger:generate
```

### Automatic Generation

The documentation is automatically regenerated on application deployment.

## Testing with Swagger UI

### 1. Try Authentication

1. Expand the **POST** `/api/v1/auth/login` endpoint
2. Click "Try it out"
3. Enter credentials:
   ```json
   {
     "email": "admin@autoerp.com",
     "password": "password123"
   }
   ```
4. Click "Execute"
5. Copy the token from the response

### 2. Authorize

1. Click the "Authorize" button (🔒 icon) at the top
2. Paste your token in the format: `Bearer YOUR_TOKEN`
3. Click "Authorize"
4. Click "Close"

### 3. Test Endpoints

Now you can test any protected endpoint:

1. Expand any endpoint (e.g., **GET** `/api/v1/customers`)
2. Click "Try it out"
3. Enter any required parameters
4. Click "Execute"
5. View the response

## Exporting Documentation

### As JSON
Download from: `http://localhost:8000/docs/api-docs.json`

### As Postman Collection
1. Open Postman
2. Click "Import"
3. Select "Link"
4. Enter: `http://localhost:8000/docs/api-docs.json`
5. Click "Continue" and "Import"

## Configuration

### L5-Swagger Configuration

The Swagger configuration is located at: `backend/config/l5-swagger.php`

Key settings:
- **Title:** AutoERP API Documentation
- **Base Path:** `/api/documentation`
- **Docs Format:** JSON
- **Annotations Path:** `app/`

### OpenAPI Version

The API documentation uses **OpenAPI 3.0.0** specification.

## Best Practices

### For API Consumers

1. **Always authenticate** before making requests to protected endpoints
2. **Handle errors gracefully** - All endpoints return consistent error formats
3. **Respect rate limits** - Implement exponential backoff for retries
4. **Use pagination** - Don't fetch all records at once
5. **Cache when possible** - Reduce unnecessary API calls

### For API Developers

1. **Add annotations to new endpoints** - Follow existing patterns
2. **Document request/response schemas** - Include examples
3. **Regenerate docs after changes** - Run `php artisan l5-swagger:generate`
4. **Test in Swagger UI** - Ensure endpoints work as documented
5. **Keep annotations up to date** - Update when making API changes

## Common Issues

### Issue: "Failed to fetch" in Swagger UI
**Solution:** Ensure the backend server is running and CORS is configured correctly.

### Issue: "401 Unauthorized" error
**Solution:** Make sure you've authenticated and included the Bearer token.

### Issue: Documentation not updating
**Solution:** Run `php artisan l5-swagger:generate` to regenerate documentation.

### Issue: Missing endpoint in documentation
**Solution:** Ensure the controller has OpenAPI annotations and regenerate docs.

## Architecture

### Clean Architecture Implementation

The API follows Clean Architecture principles:

```
Controller → Service → Repository → Model
    ↓           ↓          ↓
Validation  Business   Data Access
            Logic
```

### Module Structure

Each module follows a consistent structure:

```
Module/
├── Http/
│   ├── Controllers/     # API endpoints (documented with OpenAPI)
│   ├── Requests/       # Validation rules
│   └── Resources/      # Response transformers
├── Services/           # Business logic
├── Repositories/       # Data access
├── Models/            # Eloquent models
└── Events/            # Domain events
```

## Support

For issues or questions about the API documentation:

1. Check this guide first
2. Review the interactive Swagger UI
3. Check the codebase for examples
4. Open an issue in the repository

## Version History

### v1.0.0 (Current)
- ✅ OpenAPI 3.0 implementation
- ✅ Authentication module (8 endpoints)
- ✅ Customer Management (6 endpoints)
- ✅ Vehicle Management (6 endpoints)
- ✅ Interactive Swagger UI
- ✅ Reusable schema definitions
- ✅ Bearer token authentication
- ✅ Comprehensive examples

### Roadmap
- [ ] Document remaining 10+ modules
- [ ] Add request/response examples for all endpoints
- [ ] Add API versioning documentation
- [ ] Add WebSocket/real-time API documentation
- [ ] Add GraphQL endpoint documentation (if implemented)
- [ ] Add API changelog

## License

Proprietary. All rights reserved.

---

**Built with modern best practices for the automotive service industry** 🚗💨
