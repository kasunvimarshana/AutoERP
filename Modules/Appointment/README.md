# Appointment & Bay Scheduling Module

The Appointment module provides comprehensive appointment and service bay management functionality for vehicle service centers in the ModularSaaS application.

## Features

### Appointment Management
- **Create Appointments**: Schedule service appointments for customers with vehicle, branch, service type, and time slot selection
- **Status Workflow**: Track appointments through their lifecycle (scheduled → confirmed → in_progress → completed/cancelled)
- **Conflict Detection**: Prevent double-booking of vehicles at the same time
- **Technician Assignment**: Assign technicians to appointments
- **Reschedule**: Move appointments to different time slots with conflict checking
- **Search & Filter**: Find appointments by customer, vehicle, status, branch, or date range

### Bay Scheduling
- **Bay Management**: Define service bays with types (standard, express, diagnostic, detailing, heavy_duty)
- **Capacity Tracking**: Set bay capacity and status (available, occupied, maintenance, inactive)
- **Availability Checking**: Query available bays for specific time ranges
- **Bay Assignment**: Assign appointments to specific bays with time slots
- **Schedule Tracking**: Monitor bay utilization and schedules

## Database Schema

### Tables

#### `appointments`
- Appointment details (customer, vehicle, branch, service type)
- Scheduling information (date/time, duration)
- Status tracking and workflow timestamps
- Technician assignment
- Cancellation tracking with reason
- Audit fields (created_by, updated_by)

#### `bays`
- Bay identification (bay_number)
- Branch association
- Bay type and capacity
- Current status
- Notes

#### `bay_schedules`
- Bay-appointment assignment
- Time slot allocation (start_time, end_time)
- Schedule status
- Notes

## API Endpoints

### Appointments

#### CRUD Operations
- `GET /api/v1/appointments` - List appointments (paginated)
- `POST /api/v1/appointments` - Create appointment
- `GET /api/v1/appointments/{id}` - Get appointment with relations
- `PUT /api/v1/appointments/{id}` - Update appointment
- `DELETE /api/v1/appointments/{id}` - Delete appointment (soft delete)

#### Custom Actions
- `GET /api/v1/appointments/upcoming` - Get upcoming appointments
- `GET /api/v1/appointments/search?query={query}` - Search appointments
- `GET /api/v1/appointments/by-status?status={status}` - Filter by status
- `POST /api/v1/appointments/check-availability` - Check bay availability
- `POST /api/v1/appointments/{id}/confirm` - Confirm appointment
- `POST /api/v1/appointments/{id}/start` - Start appointment
- `POST /api/v1/appointments/{id}/complete` - Complete appointment
- `POST /api/v1/appointments/{id}/cancel` - Cancel appointment
- `POST /api/v1/appointments/{id}/reschedule` - Reschedule appointment
- `POST /api/v1/appointments/{id}/assign-bay` - Assign bay to appointment

### Bays

#### CRUD Operations
- `GET /api/v1/bays` - List bays (paginated)
- `POST /api/v1/bays` - Create bay
- `GET /api/v1/bays/{id}` - Get bay with schedules
- `PUT /api/v1/bays/{id}` - Update bay
- `DELETE /api/v1/bays/{id}` - Delete bay (soft delete)

#### Custom Actions
- `GET /api/v1/bays/available-for-branch?branch_id={id}` - Get available bays
- `GET /api/v1/bays/available-for-time-range` - Get bays available for time range

## Architecture

### Models
- **Appointment**: Service appointment entity
- **Bay**: Service bay entity
- **BaySchedule**: Bay scheduling junction table

All models include:
- Multi-tenancy support (`TenantAware` trait)
- Audit trail (`AuditTrait`)
- Soft deletes
- Eloquent factories for testing

### Enums
- **AppointmentStatus**: scheduled, confirmed, in_progress, completed, cancelled, no_show
- **BayStatus**: available, occupied, maintenance, inactive
- **BayType**: standard, express, diagnostic, detailing, heavy_duty
- **ServiceType**: oil_change, tire_rotation, brake_service, engine_diagnostic, etc.
- **BayScheduleStatus**: scheduled, active, completed, cancelled

### Repositories
- **AppointmentRepository**: Data access with conflict checking, search, filtering
- **BayRepository**: Bay management with availability checking
- **BayScheduleRepository**: Schedule management

### Services
- **AppointmentService**: Business logic with transaction handling
- **BayService**: Bay management operations
- **BayScheduleService**: Schedule operations

All services extend `BaseService` and use database transactions for data integrity.

### Controllers
- **AppointmentController**: HTTP request handling for appointments
- **BayController**: HTTP request handling for bays

All controllers follow the **Controller → Service → Repository** pattern.

## Permissions

The module defines the following permissions for RBAC:

### Appointment Permissions
- `appointment.view` - View appointments
- `appointment.create` - Create appointments
- `appointment.update` - Update appointments
- `appointment.delete` - Delete appointments
- `appointment.confirm` - Confirm appointments
- `appointment.start` - Start appointments
- `appointment.complete` - Complete appointments
- `appointment.cancel` - Cancel appointments
- `appointment.reschedule` - Reschedule appointments
- `appointment.assign-bay` - Assign bays to appointments

### Bay Permissions
- `bay.view` - View bays
- `bay.create` - Create bays
- `bay.update` - Update bays
- `bay.delete` - Delete bays

## Usage Examples

### Creating an Appointment

```json
POST /api/v1/appointments
{
  "customer_id": 1,
  "vehicle_id": 1,
  "branch_id": 1,
  "service_type": "oil_change",
  "scheduled_date_time": "2024-02-01 10:00:00",
  "duration": 60,
  "notes": "Regular maintenance"
}
```

### Checking Bay Availability

```json
POST /api/v1/appointments/check-availability
{
  "branch_id": 1,
  "start_time": "2024-02-01 10:00:00",
  "duration": 60
}
```

Response:
```json
{
  "success": true,
  "data": {
    "available": true,
    "available_bays": [
      {
        "id": 1,
        "bay_number": "BAY-001",
        "bay_type": "standard"
      }
    ],
    "requested_time": "2024-02-01 10:00:00",
    "duration": 60
  }
}
```

### Assigning a Bay

```json
POST /api/v1/appointments/1/assign-bay
{
  "bay_id": 1,
  "notes": "Standard service bay"
}
```

### Appointment Workflow

1. Create appointment (status: scheduled)
2. Confirm appointment → `POST /appointments/{id}/confirm` (status: confirmed)
3. Start appointment → `POST /appointments/{id}/start` (status: in_progress)
4. Complete appointment → `POST /appointments/{id}/complete` (status: completed)

Or cancel at any time → `POST /appointments/{id}/cancel` (status: cancelled)

## Multi-Language Support

The module supports three languages:
- English (en)
- Spanish (es)
- French (fr)

All user-facing messages are translatable via the `appointment::messages` namespace.

## Testing

### Feature Tests
- `AppointmentApiTest`: Tests for appointment API endpoints
- `BayApiTest`: Tests for bay API endpoints

### Running Tests

```bash
php artisan test --filter AppointmentApiTest
php artisan test --filter BayApiTest
```

## Database Seeding

### Permissions

```bash
php artisan module:seed Appointment
```

This will seed all appointment and bay permissions for RBAC.

## Migration

Run migrations:

```bash
php artisan migrate
```

This will create the `appointments`, `bays`, and `bay_schedules` tables.

## Dependencies

### Required Modules
- **Customer Module**: For customer and vehicle relationships
- **Organization Module**: For branch relationships
- **User Module**: For technician assignments

### Required Packages
- Laravel 11.x
- nwidart/laravel-modules
- stancl/tenancy (multi-tenancy)
- spatie/laravel-permission (RBAC)

## Code Quality

- ✅ PSR-12 compliant
- ✅ Strict types enabled
- ✅ Complete PHPDoc comments
- ✅ Type hints on all methods
- ✅ Laravel Pint formatted
- ✅ Multi-tenancy aware
- ✅ Audit trail enabled
- ✅ Soft deletes for data retention

## Security

- All routes require `auth:sanctum` authentication
- Permission-based authorization ready
- Input validation via FormRequest classes
- SQL injection prevention via Eloquent ORM
- XSS protection via API Resources
- Transaction-based data integrity

---

**Module Version**: 1.0.0  
**Laravel Version**: 11.x  
**Created**: January 2024
