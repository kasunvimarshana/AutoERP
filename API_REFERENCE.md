# AutoERP - Complete API Reference

## Overview
AutoERP provides a comprehensive REST API for vehicle service center management. All API endpoints require authentication unless specified otherwise.

**Base URL**: `http://localhost:8000/api/v1`

**Authentication**: Bearer Token (Laravel Sanctum)

---

## Authentication Endpoints

### POST /auth/register
Register a new user account.

**Public**: Yes

**Request Body**:
```json
{
  "tenant_id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "user"
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": { ... },
    "token": "1|..."
  }
}
```

### POST /auth/login
Authenticate and receive access token.

**Public**: Yes

**Request Body**:
```json
{
  "email": "admin@autoerp.com",
  "password": "password123"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Super Administrator",
      "email": "admin@autoerp.com",
      "role": "super_admin",
      "status": "active"
    },
    "token": "2|..."
  }
}
```

### POST /auth/logout
Logout and revoke current token.

**Protected**: Yes

### GET /auth/me
Get current authenticated user.

**Protected**: Yes

### POST /auth/refresh-token
Refresh authentication token.

**Protected**: Yes

### POST /auth/password/change
Change user password.

**Protected**: Yes

**Request Body**:
```json
{
  "current_password": "old_password",
  "new_password": "new_password",
  "new_password_confirmation": "new_password"
}
```

---

## Tenant Management

### GET /tenants
List all tenants with pagination and filtering.

**Protected**: Yes

**Query Parameters**:
- `search`: Search by name, email, or domain
- `status`: Filter by status (active, suspended, inactive)
- `subscription_status`: Filter by subscription status
- `per_page`: Results per page (default: 15)

### POST /tenants
Create a new tenant.

**Protected**: Yes

**Request Body**:
```json
{
  "name": "My Company",
  "slug": "my-company",
  "domain": "mycompany.com",
  "subscription_plan": "professional",
  "max_users": 100,
  "max_branches": 10
}
```

### GET /tenants/{id}
Get tenant details.

### PUT /tenants/{id}
Update tenant information.

### DELETE /tenants/{id}
Delete a tenant.

### POST /tenants/{id}/activate
Activate a tenant.

### POST /tenants/{id}/suspend
Suspend a tenant.

### POST /tenants/{id}/subscription
Update tenant subscription.

**Request Body**:
```json
{
  "subscription_status": "active",
  "subscription_plan": "enterprise",
  "subscription_started_at": "2026-01-01",
  "subscription_expires_at": "2027-01-01"
}
```

### POST /tenants/{id}/subscription/renew
Renew tenant subscription.

**Request Body**:
```json
{
  "months": 12
}
```

---

## User Management

### GET /users
List all users with filtering.

**Query Parameters**:
- `tenant_id`: Filter by tenant
- `search`: Search by name or email
- `role`: Filter by role
- `status`: Filter by status
- `per_page`: Results per page

### POST /users
Create a new user.

**Request Body**:
```json
{
  "tenant_id": 1,
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "password123",
  "role": "manager",
  "status": "active",
  "roles": ["manager"],
  "permissions": ["customers.view", "customers.create"]
}
```

### GET /users/{id}
Get user details with roles and permissions.

### PUT /users/{id}
Update user information.

### DELETE /users/{id}
Delete a user.

### POST /users/{id}/activate
Activate a user account.

### POST /users/{id}/deactivate
Deactivate a user account.

### POST /users/{id}/roles
Assign roles to user.

**Request Body**:
```json
{
  "roles": ["admin", "manager"]
}
```

### POST /users/{id}/permissions
Assign permissions to user.

**Request Body**:
```json
{
  "permissions": ["customers.view", "customers.edit"]
}
```

---

## Role & Permission Management

### GET /roles
List all roles with permissions.

### POST /roles
Create a new role.

**Request Body**:
```json
{
  "name": "technician",
  "permissions": ["job-cards.view", "job-cards.edit"]
}
```

### GET /roles/{id}
Get role details.

### PUT /roles/{id}
Update role.

### DELETE /roles/{id}
Delete role.

### POST /roles/{id}/permissions
Assign permissions to role.

### GET /permissions
List all permissions.

### POST /permissions
Create a new permission.

### GET /permissions/{id}
Get permission details.

### GET /permissions/grouped/all
Get permissions grouped by module.

---

## Customer Management

### GET /customers
List customers with pagination.

### POST /customers
Create a new customer.

**Request Body**:
```json
{
  "customer_type": "individual",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "address_line1": "123 Main St",
  "city": "New York",
  "country": "US"
}
```

### GET /customers/{id}
Get customer details.

### PUT /customers/{id}
Update customer.

### DELETE /customers/{id}
Delete customer.

---

## Vehicle Management

### GET /vehicles
List all vehicles.

### POST /vehicles
Register a new vehicle.

**Request Body**:
```json
{
  "current_customer_id": 1,
  "make": "Toyota",
  "model": "Camry",
  "year": 2023,
  "vin": "1HGBH41JXMN109186",
  "license_plate": "ABC-1234",
  "current_mileage": 15000
}
```

### GET /vehicles/{id}
Get vehicle details.

### PUT /vehicles/{id}
Update vehicle information.

### DELETE /vehicles/{id}
Delete vehicle.

### POST /vehicles/{id}/transfer-ownership
Transfer vehicle to a new owner.

**Request Body**:
```json
{
  "new_customer_id": 2,
  "reason": "sale",
  "notes": "Vehicle sold to new owner"
}
```

### POST /vehicles/{id}/update-mileage
Update vehicle mileage.

**Request Body**:
```json
{
  "mileage": 20000,
  "recorded_at": "2026-01-23"
}
```

---

## Appointment Management

### GET /appointments
List appointments.

### POST /appointments
Create a new appointment.

### GET /appointments/{id}
Get appointment details.

### PUT /appointments/{id}
Update appointment.

### DELETE /appointments/{id}
Cancel appointment.

---

## Job Card Management

### GET /job-cards
List job cards.

### POST /job-cards
Create a new job card.

### GET /job-cards/{id}
Get job card details.

### PUT /job-cards/{id}
Update job card.

### DELETE /job-cards/{id}
Delete job card.

---

## Inventory Management

### GET /inventory-items
List inventory items.

### POST /inventory-items
Create inventory item.

### GET /inventory-items/{id}
Get item details.

### PUT /inventory-items/{id}
Update inventory item.

### DELETE /inventory-items/{id}
Delete inventory item.

---

## Invoice Management

### GET /invoices
List invoices.

### POST /invoices
Create an invoice.

### GET /invoices/{id}
Get invoice details.

### PUT /invoices/{id}
Update invoice.

### DELETE /invoices/{id}
Delete invoice.

---

## CRM Management

### GET /communications
List customer communications.

**Query Parameters**:
- `customer_id`: Filter by customer
- `channel`: Filter by channel (email, sms, whatsapp, phone, in_app)
- `status`: Filter by status

### POST /communications
Create a communication record.

**Request Body**:
```json
{
  "customer_id": 1,
  "channel": "email",
  "subject": "Service Reminder",
  "message": "Your vehicle is due for service",
  "status": "pending",
  "scheduled_at": "2026-02-01T10:00:00Z"
}
```

### GET /notifications
List notifications.

### POST /notifications
Create a notification.

### POST /notifications/{id}/mark-as-read
Mark notification as read.

### GET /customer-segments
List customer segments.

### POST /customer-segments
Create a customer segment.

**Request Body**:
```json
{
  "name": "VIP Customers",
  "description": "High-value customers",
  "criteria": {
    "lifetime_value": { "min": 10000 }
  }
}
```

---

## Fleet Management

### GET /fleets
List all fleets.

**Query Parameters**:
- `customer_id`: Filter by customer
- `status`: Filter by status

### POST /fleets
Create a fleet.

**Request Body**:
```json
{
  "customer_id": 1,
  "name": "Company Fleet A",
  "description": "Main delivery fleet",
  "status": "active"
}
```

### GET /fleets/{id}
Get fleet details with vehicles.

### PUT /fleets/{id}
Update fleet.

### DELETE /fleets/{id}
Delete fleet.

### POST /fleets/{id}/vehicles
Add vehicle to fleet.

**Request Body**:
```json
{
  "vehicle_id": 5
}
```

### DELETE /fleets/{id}/vehicles/{vehicleId}
Remove vehicle from fleet.

---

## Standard Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message"
}
```

### Validation Error
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Rate Limiting

API requests are rate-limited to:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated requests

---

## Pagination

Paginated responses include:
```json
{
  "current_page": 1,
  "data": [...],
  "first_page_url": "...",
  "last_page": 10,
  "per_page": 15,
  "total": 150
}
```

---

## Demo Credentials

For testing purposes:
- **Super Admin**: admin@autoerp.com / password123
- **Admin**: admin@demo.com / password123
- **Manager**: manager@demo.com / password123
