<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'ModularSaaS API Documentation',
    description: 'Complete API documentation for ModularSaaS - A production-ready, enterprise-grade modular SaaS application built with Laravel 11 and Vue.js 3. This API follows REST principles and implements multi-tenancy, RBAC/ABAC authorization, and enterprise security standards.',
    contact: new OA\Contact(name: 'API Support', email: 'support@modularsaas.com'),
    license: new OA\License(name: 'MIT', url: 'https://opensource.org/licenses/MIT')
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter your bearer token in the format: Bearer {token}'
)]
#[OA\Tag(name: 'Authentication', description: 'API Endpoints for user authentication, registration, and session management')]
#[OA\Tag(name: 'Users', description: 'API Endpoints for user management (CRUD operations and role management)')]
#[OA\Tag(name: 'Customers', description: 'API Endpoints for customer management with multi-tenancy support')]
#[OA\Tag(name: 'Vehicles', description: 'API Endpoints for vehicle management and tracking')]
#[OA\Tag(name: 'Service Records', description: 'API Endpoints for vehicle service records with cross-branch tracking')]
/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1, description="User ID"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2024-01-22T10:00:00Z", description="Email verification timestamp"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-22T10:00:00Z", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-22T10:00:00Z", description="Last update timestamp"),
 *     @OA\Property(property="roles", type="array", description="User roles", @OA\Items(ref="#/components/schemas/Role")),
 *     @OA\Property(property="permissions", type="array", description="User permissions", @OA\Items(ref="#/components/schemas/Permission"))
 * )
 *
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     title="Role",
 *     description="User role for RBAC",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="admin", description="Role name"),
 *     @OA\Property(property="guard_name", type="string", example="web", description="Guard name"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     title="Permission",
 *     description="User permission for fine-grained access control",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="user.create", description="Permission name"),
 *     @OA\Property(property="guard_name", type="string", example="web"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     title="Error Response",
 *     description="Standard error response structure",
 *     @OA\Property(property="success", type="boolean", example=false, description="Indicates the request failed"),
 *     @OA\Property(property="message", type="string", example="Error message", description="Human-readable error message"),
 *     @OA\Property(property="data", type="object", nullable=true, description="Additional error details (optional)")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     title="Validation Error Response",
 *     description="Validation error response with field-specific errors",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Field-specific validation errors",
 *         example={"email": {"The email field is required."}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *     title="Customer",
 *     description="Customer model",
 *     @OA\Property(property="id", type="integer", example=1, description="Customer ID"),
 *     @OA\Property(property="customer_number", type="string", example="CUST-001", description="Unique customer number"),
 *     @OA\Property(property="first_name", type="string", example="John", description="Customer's first name"),
 *     @OA\Property(property="last_name", type="string", example="Doe", description="Customer's last name"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="john.doe@example.com", description="Customer's email"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+1234567890", description="Customer's phone number"),
 *     @OA\Property(property="mobile", type="string", nullable=true, example="+1234567890", description="Customer's mobile number"),
 *     @OA\Property(property="address_line_1", type="string", nullable=true, example="123 Main St", description="Address line 1"),
 *     @OA\Property(property="address_line_2", type="string", nullable=true, example="Apt 4B", description="Address line 2"),
 *     @OA\Property(property="city", type="string", nullable=true, example="New York", description="City"),
 *     @OA\Property(property="state", type="string", nullable=true, example="NY", description="State"),
 *     @OA\Property(property="postal_code", type="string", nullable=true, example="10001", description="Postal code"),
 *     @OA\Property(property="country", type="string", nullable=true, example="USA", description="Country"),
 *     @OA\Property(property="status", type="string", example="active", description="Customer status"),
 *     @OA\Property(property="customer_type", type="string", example="individual", description="Customer type (individual/corporate)"),
 *     @OA\Property(property="company_name", type="string", nullable=true, example="Acme Corp", description="Company name for corporate customers"),
 *     @OA\Property(property="tax_id", type="string", nullable=true, example="12-3456789", description="Tax ID"),
 *     @OA\Property(property="receive_notifications", type="boolean", example=true, description="Receive notifications flag"),
 *     @OA\Property(property="receive_marketing", type="boolean", example=false, description="Receive marketing flag"),
 *     @OA\Property(property="last_service_date", type="string", format="date-time", nullable=true, example="2024-01-15T10:00:00Z", description="Last service date"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="VIP customer", description="Notes"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-22T10:00:00Z"),
 *     @OA\Property(property="vehicles", type="array", description="Customer vehicles", @OA\Items(ref="#/components/schemas/Vehicle"))
 * )
 *
 * @OA\Schema(
 *     schema="Vehicle",
 *     type="object",
 *     title="Vehicle",
 *     description="Vehicle model",
 *     @OA\Property(property="id", type="integer", example=1, description="Vehicle ID"),
 *     @OA\Property(property="customer_id", type="integer", example=1, description="Owner customer ID"),
 *     @OA\Property(property="vehicle_number", type="string", example="VEH-001", description="Unique vehicle number"),
 *     @OA\Property(property="registration_number", type="string", example="ABC-123", description="Vehicle registration number"),
 *     @OA\Property(property="vin", type="string", nullable=true, example="1HGBH41JXMN109186", description="Vehicle Identification Number"),
 *     @OA\Property(property="make", type="string", example="Toyota", description="Vehicle make"),
 *     @OA\Property(property="model", type="string", example="Camry", description="Vehicle model"),
 *     @OA\Property(property="year", type="integer", example=2023, description="Manufacturing year"),
 *     @OA\Property(property="color", type="string", nullable=true, example="Silver", description="Vehicle color"),
 *     @OA\Property(property="engine_number", type="string", nullable=true, example="ENG123456", description="Engine number"),
 *     @OA\Property(property="chassis_number", type="string", nullable=true, example="CHS123456", description="Chassis number"),
 *     @OA\Property(property="fuel_type", type="string", nullable=true, example="Petrol", description="Fuel type"),
 *     @OA\Property(property="transmission", type="string", nullable=true, example="Automatic", description="Transmission type"),
 *     @OA\Property(property="current_mileage", type="integer", example=15000, description="Current mileage"),
 *     @OA\Property(property="purchase_date", type="string", format="date", nullable=true, example="2023-01-15", description="Purchase date"),
 *     @OA\Property(property="registration_date", type="string", format="date", nullable=true, example="2023-01-20", description="Registration date"),
 *     @OA\Property(property="insurance_expiry", type="string", format="date", nullable=true, example="2024-12-31", description="Insurance expiry date"),
 *     @OA\Property(property="insurance_provider", type="string", nullable=true, example="ABC Insurance", description="Insurance provider"),
 *     @OA\Property(property="insurance_policy_number", type="string", nullable=true, example="POL123456", description="Insurance policy number"),
 *     @OA\Property(property="status", type="string", example="active", description="Vehicle status"),
 *     @OA\Property(property="last_service_date", type="string", format="date-time", nullable=true, example="2024-01-10T10:00:00Z", description="Last service date"),
 *     @OA\Property(property="next_service_mileage", type="integer", nullable=true, example=20000, description="Next service mileage"),
 *     @OA\Property(property="next_service_date", type="string", format="date", nullable=true, example="2024-06-10", description="Next service date"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Requires premium oil", description="Notes"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-22T10:00:00Z"),
 *     @OA\Property(property="customer", ref="#/components/schemas/Customer", description="Vehicle owner"),
 *     @OA\Property(property="service_records", type="array", description="Service records", @OA\Items(ref="#/components/schemas/VehicleServiceRecord"))
 * )
 *
 * @OA\Schema(
 *     schema="VehicleServiceRecord",
 *     type="object",
 *     title="Vehicle Service Record",
 *     description="Vehicle service record model with cross-branch tracking",
 *     @OA\Property(property="id", type="integer", example=1, description="Service record ID"),
 *     @OA\Property(property="vehicle_id", type="integer", example=1, description="Vehicle ID"),
 *     @OA\Property(property="customer_id", type="integer", example=1, description="Customer ID"),
 *     @OA\Property(property="service_number", type="string", example="SRV-001", description="Unique service number"),
 *     @OA\Property(property="branch_id", type="string", nullable=true, example="BRANCH-01", description="Service branch ID"),
 *     @OA\Property(property="service_date", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Service date"),
 *     @OA\Property(property="mileage_at_service", type="integer", example=15000, description="Mileage at service"),
 *     @OA\Property(property="service_type", type="string", example="Oil Change", description="Service type"),
 *     @OA\Property(property="service_description", type="string", nullable=true, example="Full synthetic oil change", description="Service description"),
 *     @OA\Property(property="parts_used", type="string", nullable=true, example="Oil filter, Engine oil", description="Parts used"),
 *     @OA\Property(property="labor_cost", type="number", format="float", example=50.00, description="Labor cost"),
 *     @OA\Property(property="parts_cost", type="number", format="float", example=75.00, description="Parts cost"),
 *     @OA\Property(property="total_cost", type="number", format="float", example=125.00, description="Total cost"),
 *     @OA\Property(property="technician_name", type="string", nullable=true, example="John Smith", description="Technician name"),
 *     @OA\Property(property="technician_id", type="integer", nullable=true, example=1, description="Technician ID"),
 *     @OA\Property(property="next_service_mileage", type="integer", nullable=true, example=20000, description="Next service mileage"),
 *     @OA\Property(property="next_service_date", type="string", format="date", nullable=true, example="2024-06-15", description="Next service date"),
 *     @OA\Property(property="status", type="string", example="completed", description="Service status"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Customer approved additional service", description="Notes"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T14:00:00Z"),
 *     @OA\Property(property="vehicle", ref="#/components/schemas/Vehicle", description="Service vehicle"),
 *     @OA\Property(property="customer", ref="#/components/schemas/Customer", description="Service customer")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     title="Pagination Metadata",
 *     description="Pagination metadata for list responses",
 *     @OA\Property(property="current_page", type="integer", example=1, description="Current page number"),
 *     @OA\Property(property="from", type="integer", nullable=true, example=1, description="First item number on current page"),
 *     @OA\Property(property="last_page", type="integer", example=5, description="Last page number"),
 *     @OA\Property(property="per_page", type="integer", example=15, description="Items per page"),
 *     @OA\Property(property="to", type="integer", nullable=true, example=15, description="Last item number on current page"),
 *     @OA\Property(property="total", type="integer", example=75, description="Total number of items")
 * )
 */
class OpenApiController extends Controller
{
    // This class exists solely for OpenAPI annotations
}
