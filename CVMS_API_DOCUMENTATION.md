# CVMS Module API Documentation

## Overview

The Customer & Vehicle Management System (CVMS) module provides comprehensive REST APIs for managing customers, vehicles, and service records in a multi-tenant, multi-branch vehicle service center environment.

## Authentication

All API endpoints require authentication using Laravel Sanctum. Include the bearer token in the request header:

```
Authorization: Bearer {your-token-here}
```

## Base URL

```
{app-url}/api/v1
```

## Modules

### 1. Customer Management

#### List Customers
```http
GET /customers?paginate=true&per_page=15
```

**Response:**
```json
{
  "success": true,
  "message": "Customers retrieved successfully",
  "data": [
    {
      "id": 1,
      "customer_number": "CUST-20260122-0001",
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "phone": "+1234567890",
      "customer_type": "individual",
      "status": "active"
    }
  ]
}
```

#### Create Customer
```http
POST /customers
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "mobile": "+1234567891",
  "customer_type": "individual",
  "address_line_1": "123 Main St",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "USA"
}
```

#### Get Customer with Vehicles
```http
GET /customers/{id}/vehicles
```

#### Search Customers
```http
GET /customers/search?query=john
```

#### Get Customer Statistics
```http
GET /customers/{id}/statistics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_vehicles": 3,
    "active_vehicles": 2,
    "total_service_records": 15,
    "last_service_date": "2026-01-15"
  }
}
```

### 2. Vehicle Management

#### List Vehicles
```http
GET /vehicles?paginate=true&per_page=15
```

#### Create Vehicle
```http
POST /vehicles
Content-Type: application/json

{
  "customer_id": 1,
  "registration_number": "ABC-1234",
  "vin": "1HGBH41JXMN109186",
  "make": "Toyota",
  "model": "Camry",
  "year": 2023,
  "color": "Silver",
  "engine_number": "ENG123456",
  "chassis_number": "CHS123456",
  "fuel_type": "gasoline",
  "transmission_type": "automatic",
  "current_mileage": 15000
}
```

#### Get Vehicles Due for Service
```http
GET /vehicles/due-for-service
```

#### Get Vehicles with Expiring Insurance
```http
GET /vehicles/expiring-insurance?days=30
```

#### Update Vehicle Mileage
```http
PATCH /vehicles/{id}/mileage
Content-Type: application/json

{
  "mileage": 25000
}
```

#### Transfer Vehicle Ownership
```http
POST /vehicles/{id}/transfer-ownership
Content-Type: application/json

{
  "new_customer_id": 2,
  "transfer_date": "2026-01-22",
  "notes": "Sold to new owner"
}
```

### 3. Service Record Management

#### List Service Records
```http
GET /service-records?paginate=true&per_page=15
```

#### Create Service Record
```http
POST /service-records
Content-Type: application/json

{
  "vehicle_id": 1,
  "customer_id": 1,
  "branch_id": "BRANCH-1",
  "service_date": "2026-01-22",
  "mileage_at_service": 25000,
  "service_type": "regular",
  "service_description": "Regular oil change and inspection",
  "labor_cost": 50.00,
  "parts_cost": 75.00,
  "technician_name": "Mike Johnson",
  "notes": "Customer requested premium oil",
  "next_service_mileage": 30000,
  "next_service_date": "2026-04-22"
}
```

#### Get Service Records by Vehicle
```http
GET /vehicles/{vehicleId}/service-records
```

#### Get Service Records by Customer
```http
GET /customers/{customerId}/service-records
```

#### Get Cross-Branch Service History
```http
GET /vehicles/{vehicleId}/cross-branch-history
```

**Response:**
```json
{
  "success": true,
  "message": "Service history retrieved successfully",
  "data": [
    {
      "id": 1,
      "service_number": "SVC-20260122-0001",
      "branch_id": "BRANCH-1",
      "service_date": "2026-01-15",
      "service_type": "regular",
      "total_cost": 125.00,
      "status": "completed"
    },
    {
      "id": 2,
      "service_number": "SVC-20260110-0042",
      "branch_id": "BRANCH-2",
      "service_date": "2026-01-10",
      "service_type": "repair",
      "total_cost": 350.00,
      "status": "completed"
    }
  ]
}
```

#### Get Vehicle Service History Summary
```http
GET /vehicles/{vehicleId}/history-summary
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_services": 8,
    "branches_serviced_at": 3,
    "branch_breakdown": {
      "BRANCH-1": {
        "count": 4,
        "total_cost": 850.00
      },
      "BRANCH-2": {
        "count": 3,
        "total_cost": 1200.00
      },
      "BRANCH-3": {
        "count": 1,
        "total_cost": 200.00
      }
    },
    "service_types": {
      "regular": 5,
      "repair": 2,
      "inspection": 1
    },
    "total_spent": 2250.00,
    "average_cost": 281.25,
    "last_service": "2026-01-15"
  }
}
```

#### Get Service Records by Branch
```http
GET /service-records/by-branch?branch_id=BRANCH-1
```

#### Get Service Records by Service Type
```http
GET /service-records/by-service-type?service_type=regular
```

#### Get Service Records by Status
```http
GET /service-records/by-status?status=completed
```

#### Get Service Records by Date Range
```http
GET /service-records/by-date-range?start_date=2026-01-01&end_date=2026-01-31
```

#### Get Pending Service Records
```http
GET /service-records/pending
```

#### Get In-Progress Service Records
```http
GET /service-records/in-progress
```

#### Complete a Service Record
```http
POST /service-records/{id}/complete
```

#### Cancel a Service Record
```http
POST /service-records/{id}/cancel
Content-Type: application/json

{
  "reason": "Customer cancelled appointment"
}
```

#### Get Vehicle Service Statistics
```http
GET /vehicles/{vehicleId}/service-statistics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_services": 8,
    "completed_services": 7,
    "total_cost": 2250.00,
    "average_cost": 281.25,
    "last_service_date": "2026-01-15",
    "service_types": {
      "regular": 5,
      "repair": 2,
      "inspection": 1
    }
  }
}
```

#### Get Customer Service Statistics
```http
GET /customers/{customerId}/service-statistics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_services": 24,
    "completed_services": 22,
    "total_cost": 6750.00,
    "average_cost": 306.82,
    "last_service_date": "2026-01-15",
    "vehicles_serviced": 3,
    "branches_used": 4
  }
}
```

#### Search Service Records
```http
GET /service-records/search?query=oil+change
```

## Service Types

- `regular` - Regular maintenance service
- `major` - Major service including comprehensive checks
- `repair` - Repair service
- `inspection` - Vehicle inspection
- `warranty` - Warranty service
- `emergency` - Emergency service

## Service Status

- `pending` - Service scheduled but not started
- `in_progress` - Service currently being performed
- `completed` - Service completed
- `cancelled` - Service cancelled

## Customer Types

- `individual` - Individual customer
- `business` - Business customer

## Customer Status

- `active` - Active customer
- `inactive` - Inactive customer
- `blocked` - Blocked customer

## Vehicle Status

- `active` - Active vehicle
- `inactive` - Inactive vehicle
- `sold` - Vehicle sold
- `scrapped` - Vehicle scrapped

## Error Responses

All endpoints may return error responses in the following format:

```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field_name": ["Error details"]
  }
}
```

### Common HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Pagination

All list endpoints support pagination:

```http
GET /customers?paginate=true&per_page=15&page=2
```

**Paginated Response:**
```json
{
  "success": true,
  "message": "Customers retrieved successfully",
  "data": {
    "current_page": 2,
    "data": [...],
    "first_page_url": "http://example.com/api/v1/customers?page=1",
    "from": 16,
    "last_page": 5,
    "last_page_url": "http://example.com/api/v1/customers?page=5",
    "next_page_url": "http://example.com/api/v1/customers?page=3",
    "path": "http://example.com/api/v1/customers",
    "per_page": 15,
    "prev_page_url": "http://example.com/api/v1/customers?page=1",
    "to": 30,
    "total": 75
  }
}
```

## Multi-Tenancy

All endpoints automatically scope data to the current tenant based on the authenticated user's tenant context. Cross-tenant data access is prevented at the database level.

## Cross-Branch Operations

Service records support cross-branch tracking, allowing vehicles to be serviced at any branch while maintaining complete service history across all locations. Use the `branch_id` field to track which branch performed each service.

## Best Practices

1. **Always validate input** - Use the provided validation in Form Requests
2. **Handle errors gracefully** - Check HTTP status codes and error messages
3. **Use pagination** - For list endpoints with large datasets
4. **Include authentication** - All requests require a valid bearer token
5. **Track service history** - Use cross-branch endpoints for complete vehicle history
6. **Update vehicle mileage** - Keep mileage current with each service
7. **Set next service dates** - Schedule future maintenance during service completion

## Rate Limiting

API endpoints are rate-limited to prevent abuse. Current limits:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated requests

## Support

For API support or questions, refer to:
- Technical Documentation: ARCHITECTURE.md
- Security Guidelines: SECURITY.md
- Deployment Guide: DEPLOYMENT.md
