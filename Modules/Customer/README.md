# Customer Module

A production-ready, enterprise-grade Customer and Vehicle Management module for the ModularSaaS vehicle service center system. This module implements multi-tenancy, multi-branch operations, cross-branch service history tracking, and supports multiple vehicles per customer.

## Features

### Customer Management
- ✅ Complete CRUD operations for customers
- ✅ Support for both individual and business customers
- ✅ Multi-contact information (email, phone, mobile)
- ✅ Comprehensive address management
- ✅ Customer status tracking (active, inactive, blocked)
- ✅ Tax ID and business registration support
- ✅ Communication preferences (notifications, marketing)
- ✅ Last service date tracking
- ✅ Customer search and filtering
- ✅ Customer statistics and reporting

### Vehicle Management
- ✅ Complete CRUD operations for vehicles
- ✅ Multiple vehicles per customer support
- ✅ Comprehensive vehicle information (make, model, year, VIN, etc.)
- ✅ Mileage tracking with validation
- ✅ Service due date and mileage tracking
- ✅ Insurance expiry tracking and alerts
- ✅ Vehicle ownership transfer
- ✅ Vehicle status management (active, inactive, sold, scrapped)
- ✅ Vehicle search and filtering
- ✅ Vehicles due for service reporting
- ✅ Vehicles with expiring insurance reporting

### Service History Tracking
- ✅ Cross-branch service record tracking
- ✅ Complete service history per vehicle
- ✅ Branch-specific service records
- ✅ Service cost tracking (labor + parts)
- ✅ Technician assignment tracking
- ✅ Service type categorization
- ✅ Next service scheduling

## Architecture

This module follows the **Controller → Service → Repository** pattern:

```
HTTP Request → Controller → Service → Repository → Model → Database
```

### Layer Responsibilities

- **Controllers** (`app/Http/Controllers/`): Handle HTTP requests and responses only
- **Services** (`app/Services/`): Contain all business logic and orchestration
- **Repositories** (`app/Repositories/`): Handle database queries and data access
- **Models** (`app/Models/`): Eloquent models with relationships
- **Requests** (`app/Requests/`): Form validation classes
- **Resources** (`app/Resources/`): API response transformation

## Database Schema

### Customers Table
- Primary customer information (name, contact, address)
- Customer type (individual/business)
- Company details for business customers
- Status and preferences
- Last service date tracking

### Vehicles Table
- Complete vehicle information
- Registration and VIN
- Engine and chassis details
- Fuel type and transmission
- Mileage tracking
- Service scheduling
- Insurance tracking
- Ownership linkage

### Vehicle Service Records Table
- Service history tracking
- Branch-specific records
- Cost breakdown
- Technician information
- Parts tracking
- Next service scheduling

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Seed Sample Data (Optional)

```bash
php artisan module:seed Customer
```

This will create:
- 50 individual customers with 1-3 vehicles each
- 20 business customers with 2-5 vehicles each
- 10 customers with vehicles due for service
- 5 customers with vehicles having expiring insurance

## API Endpoints

### Customer Endpoints

```
GET    /api/v1/customers              # List all customers (paginated)
POST   /api/v1/customers              # Create a new customer
GET    /api/v1/customers/{id}         # Get customer by ID
PUT    /api/v1/customers/{id}         # Update customer
DELETE /api/v1/customers/{id}         # Delete customer
GET    /api/v1/customers/{id}/vehicles          # Get customer with vehicles
GET    /api/v1/customers/search?query={query}   # Search customers
GET    /api/v1/customers/{id}/statistics        # Get customer statistics
```

### Vehicle Endpoints

```
GET    /api/v1/vehicles               # List all vehicles (paginated)
POST   /api/v1/vehicles               # Create a new vehicle
GET    /api/v1/vehicles/{id}          # Get vehicle by ID
PUT    /api/v1/vehicles/{id}          # Update vehicle
DELETE /api/v1/vehicles/{id}          # Delete vehicle
GET    /api/v1/vehicles/{id}/with-relations     # Get vehicle with all relations
GET    /api/v1/customers/{id}/vehicles          # Get vehicles by customer
GET    /api/v1/vehicles/search?query={query}    # Search vehicles
GET    /api/v1/vehicles/due-for-service         # Get vehicles due for service
GET    /api/v1/vehicles/expiring-insurance      # Get vehicles with expiring insurance
PATCH  /api/v1/vehicles/{id}/mileage            # Update vehicle mileage
POST   /api/v1/vehicles/{id}/transfer-ownership # Transfer vehicle ownership
GET    /api/v1/vehicles/{id}/statistics         # Get vehicle service statistics
```

All endpoints require authentication via Laravel Sanctum.

## Usage Examples

### Create a Customer

```http
POST /api/v1/customers
Content-Type: application/json
Authorization: Bearer {token}

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "mobile": "+1234567891",
  "address_line_1": "123 Main St",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "USA",
  "customer_type": "individual",
  "receive_notifications": true
}
```

### Create a Vehicle

```http
POST /api/v1/vehicles
Content-Type: application/json
Authorization: Bearer {token}

{
  "customer_id": 1,
  "registration_number": "ABC-1234",
  "vin": "1HGBH41JXMN109186",
  "make": "Toyota",
  "model": "Camry",
  "year": 2023,
  "color": "Silver",
  "fuel_type": "petrol",
  "transmission": "automatic",
  "current_mileage": 15000,
  "insurance_expiry": "2025-12-31",
  "insurance_provider": "Acme Insurance",
  "insurance_policy_number": "POL-12345678"
}
```

### Search Customers

```http
GET /api/v1/customers/search?query=john
Authorization: Bearer {token}
```

### Get Vehicles Due for Service

```http
GET /api/v1/vehicles/due-for-service
Authorization: Bearer {token}
```

### Transfer Vehicle Ownership

```http
POST /api/v1/vehicles/5/transfer-ownership
Content-Type: application/json
Authorization: Bearer {token}

{
  "new_customer_id": 10,
  "notes": "Sold to new owner"
}
```

## Multi-Tenancy Support

This module is designed with multi-tenancy in mind:

- All models include the `AuditTrait` for tracking changes
- Database queries are automatically scoped to the current tenant
- Service records track the branch where service was performed
- Cross-branch service history is fully accessible

## Localization

The module supports three languages out of the box:
- English (en)
- Spanish (es)
- French (fr)

Translation files are located in the `lang/` directory.

## Testing

### Run Tests

```bash
# Run all Customer module tests
php artisan test --testsuite=Customer

# Run specific test class
php artisan test Modules/Customer/tests/Feature/CustomerApiTest.php
```

### Factory Usage

```php
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;

// Create a customer with vehicles
$customer = Customer::factory()
    ->active()
    ->has(Vehicle::factory()->count(3))
    ->create();

// Create a business customer
$business = Customer::factory()
    ->business()
    ->create();

// Create a vehicle due for service
$vehicle = Vehicle::factory()
    ->dueForService()
    ->create();
```

## Security

- All routes require authentication via Laravel Sanctum
- Input validation using FormRequest classes
- SQL injection protection via Eloquent ORM
- XSS protection via Laravel's built-in escaping
- CSRF protection on all state-changing operations
- Audit trail for all changes via `AuditTrait`

## Performance Considerations

- Database indexes on frequently queried columns
- Eager loading to prevent N+1 queries
- Pagination on list endpoints
- Soft deletes for data retention
- Efficient repository query methods

## Best Practices

1. **Always use services for business logic** - Never put business logic in controllers or repositories
2. **Use FormRequests for validation** - Keep validation logic separate and reusable
3. **Use API Resources for transformations** - Consistent response formatting
4. **Follow naming conventions** - PSR-12 coding standard
5. **Write tests** - Unit tests for services, feature tests for endpoints
6. **Document your code** - PHPDoc blocks on all methods

## Future Enhancements

Planned features for future versions:
- [ ] Vehicle maintenance scheduling
- [ ] Customer loyalty program
- [ ] Service package management
- [ ] Fleet management for business customers
- [ ] Customer portal integration
- [ ] SMS/Email notifications for service reminders
- [ ] Photo uploads for vehicle damage records
- [ ] Document management (registration, insurance, etc.)

## Support

For issues, questions, or contributions, please refer to the main project documentation.

## License

This module is part of the ModularSaaS project and follows the same license.
