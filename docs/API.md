# API Documentation

## Base URL

```
https://api.yourdomain.com/api/v1
```

## Authentication

All API requests require authentication using Laravel Sanctum tokens.

```http
Authorization: Bearer {token}
```

## Response Format

### Success Response

```json
{
  "success": true,
  "data": { ... },
  "message": "Optional success message"
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message",
  "errors": { ... }  // Validation errors
}
```

## Customer API

### List Customers

```http
GET /customers
```

Query Parameters:
- `per_page` (int): Items per page (default: 15)
- `search` (string): Search term for name, email, or phone
- `page` (int): Page number

Response:
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "+1234567890",
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### Get Customer

```http
GET /customers/{id}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "address": "123 Main St",
    "city": "Springfield",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

### Create Customer

```http
POST /customers
```

Request Body:
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "address": "123 Main St",
  "city": "Springfield",
  "state": "IL",
  "zip_code": "62701",
  "country": "USA"
}
```

Response: 201 Created

### Update Customer

```http
PUT /customers/{id}
```

Request Body: Same as Create

Response: 200 OK

### Delete Customer

```http
DELETE /customers/{id}
```

Response: 200 OK

### Customer Statistics

```http
GET /customers/statistics
```

Response:
```json
{
  "success": true,
  "data": {
    "total": 1000,
    "new_this_month": 50,
    "with_vehicles": 750,
    "active": 900
  }
}
```

### Merge Customers

```http
POST /customers/merge
```

Request Body:
```json
{
  "primary_customer_id": 1,
  "duplicate_customer_id": 2
}
```

## Vehicle API

### List Vehicles

```http
GET /vehicles
```

Query Parameters:
- `per_page` (int): Items per page
- `search` (string): Search term
- `customer_id` (int): Filter by customer
- `needing_service` (bool): Filter vehicles needing service

### Register Vehicle

```http
POST /vehicles
```

Request Body:
```json
{
  "customer_id": 1,
  "vin": "1HGBH41JXMN109186",
  "registration_number": "ABC123",
  "make": "Toyota",
  "model": "Camry",
  "year": 2023,
  "color": "Blue",
  "current_mileage": 5000,
  "fuel_type": "petrol",
  "transmission_type": "automatic"
}
```

### Transfer Ownership

```http
POST /vehicles/{id}/transfer-ownership
```

Request Body:
```json
{
  "new_customer_id": 2,
  "transfer_date": "2024-01-15",
  "notes": "Sold to new owner"
}
```

### Record Meter Reading

```http
POST /vehicles/{id}/meter-readings
```

Request Body:
```json
{
  "mileage": 10500,
  "recorded_at": "2024-01-15T10:00:00Z",
  "notes": "Regular service check"
}
```

### Get Service History

```http
GET /vehicles/{id}/service-history
```

Response:
```json
{
  "success": true,
  "data": {
    "vehicle": { ... },
    "service_records": [ ... ],
    "meter_readings": [ ... ],
    "job_cards": [ ... ],
    "appointments": [ ... ]
  }
}
```

## Appointment API

### List Appointments

```http
GET /appointments
```

Query Parameters:
- `branch_id` (int): Filter by branch
- `status` (string): Filter by status
- `date` (date): Filter by date

### Create Appointment

```http
POST /appointments
```

Request Body:
```json
{
  "customer_id": 1,
  "vehicle_id": 1,
  "branch_id": 1,
  "appointment_date": "2024-01-20",
  "appointment_time": "10:00",
  "estimated_duration": 120,
  "notes": "Oil change and inspection"
}
```

## Job Card API

### List Job Cards

```http
GET /job-cards
```

### Create Job Card

```http
POST /job-cards
```

Request Body:
```json
{
  "customer_id": 1,
  "vehicle_id": 1,
  "branch_id": 1,
  "appointment_id": 1,
  "priority": "normal",
  "items": [
    {
      "item_type": "service",
      "description": "Oil Change",
      "quantity": 1,
      "unit_price": 50.00
    }
  ]
}
```

## Invoice API

### List Invoices

```http
GET /invoices
```

### Create Invoice

```http
POST /invoices
```

Request Body:
```json
{
  "customer_id": 1,
  "job_card_id": 1,
  "items": [ ... ],
  "tax_rate": 0.10,
  "discount_amount": 0,
  "due_date": "2024-02-01"
}
```

### Record Payment

```http
POST /invoices/{id}/payments
```

Request Body:
```json
{
  "amount": 150.00,
  "payment_method": "card",
  "payment_date": "2024-01-15",
  "reference_number": "TXN123456"
}
```

## Error Codes

- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Internal Server Error

## Rate Limiting

- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated requests

## Pagination

All list endpoints support pagination:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

Response includes:
- `data`: Array of items
- `current_page`: Current page number
- `per_page`: Items per page
- `total`: Total items
- `last_page`: Last page number

## Filtering and Sorting

Most list endpoints support:
- `sort_by`: Field to sort by
- `sort_order`: `asc` or `desc`
- `filter[field]`: Filter by field value

## Versioning

API version is specified in the URL path: `/api/v1/`

Breaking changes will result in a new version: `/api/v2/`
