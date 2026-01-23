<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "AutoERP is a production-ready, enterprise-level modular SaaS application for vehicle service centers and auto repair garages. Built with Laravel 11 backend and Vue.js 3 frontend, implementing Clean Architecture principles with a strict Controller → Service → Repository pattern. Features comprehensive multi-tenancy support, event-driven architecture, full RBAC, and enterprise-grade security.",
    title: "AutoERP API Documentation",
    contact: new OA\Contact(
        email: "support@autoerp.com"
    )
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local Development Server"
)]
#[OA\Server(
    url: "https://api.autoerp.com",
    description: "Production Server"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    description: "Laravel Sanctum Bearer Token Authentication",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "token"
)]
#[OA\Tag(
    name: "Authentication",
    description: "Authentication and authorization endpoints including register, login, logout, password management, and token refresh"
)]
#[OA\Tag(
    name: "User Management",
    description: "User CRUD operations, activation/deactivation, role assignment, and tenant-scoped user management"
)]
#[OA\Tag(
    name: "Tenant Management",
    description: "Multi-tenant architecture with tenant CRUD, subscription management, activation/suspension, and user limits"
)]
#[OA\Tag(
    name: "Role & Permission Management",
    description: "RBAC/ABAC with role CRUD, permission assignment, and 73 granular permissions across all modules"
)]
#[OA\Tag(
    name: "Customer Management",
    description: "Customer profiles (individual and business), multi-vehicle ownership, service scheduling, and customer analytics"
)]
#[OA\Tag(
    name: "Vehicle Management",
    description: "Vehicle CRUD, ownership transfer with history, mileage tracking, and service intervals"
)]
#[OA\Tag(
    name: "Appointment Management",
    description: "Service bay management, appointment scheduling, resource allocation, confirmation/cancellation workflows"
)]
#[OA\Tag(
    name: "Job Card Management",
    description: "Comprehensive job cards, task assignment/tracking, digital inspection with photos, workflow state machine"
)]
#[OA\Tag(
    name: "Inventory Management",
    description: "Parts and inventory management, stock movement tracking, low stock alerts, dummy items support"
)]
#[OA\Tag(
    name: "Purchase Order Management",
    description: "Supplier management, purchase order workflows, receiving and approval processes"
)]
#[OA\Tag(
    name: "Invoicing",
    description: "Invoice generation from job cards, service packages support, overdue tracking"
)]
#[OA\Tag(
    name: "Payment Management",
    description: "Multiple payment methods, payment application to invoices, driver commissions tracking"
)]
#[OA\Tag(
    name: "CRM & Communication",
    description: "Multi-channel communication (email, SMS, WhatsApp), automated notifications, customer segmentation"
)]
#[OA\Tag(
    name: "Fleet Management",
    description: "Fleet management for business customers, vehicle assignment, maintenance scheduling, service due tracking"
)]
#[OA\Tag(
    name: "Reporting & Analytics",
    description: "Custom report generation, KPI tracking, performance metrics, business intelligence"
)]
#[OA\Schema(
    schema: "PaginationMeta",
    properties: [
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "from", type: "integer", example: 1),
        new OA\Property(property: "last_page", type: "integer", example: 10),
        new OA\Property(property: "per_page", type: "integer", example: 15),
        new OA\Property(property: "to", type: "integer", example: 15),
        new OA\Property(property: "total", type: "integer", example: 150)
    ]
)]
#[OA\Schema(
    schema: "SuccessResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", type: "object")
    ]
)]
#[OA\Schema(
    schema: "ErrorResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(property: "message", type: "string", example: "An error occurred"),
        new OA\Property(
            property: "errors",
            type: "object",
            additionalProperties: new OA\AdditionalProperties(
                type: "array",
                items: new OA\Items(type: "string")
            )
        )
    ]
)]
#[OA\Schema(
    schema: "ValidationErrorResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(property: "message", type: "string", example: "Validation failed"),
        new OA\Property(
            property: "errors",
            type: "object",
            example: [
                "email" => ["The email field is required."],
                "password" => ["The password must be at least 8 characters."]
            ]
        )
    ]
)]
#[OA\Schema(
    schema: "Customer",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "customer_type", type: "string", enum: ["individual", "business"], example: "individual"),
        new OA\Property(property: "first_name", type: "string", example: "John"),
        new OA\Property(property: "last_name", type: "string", example: "Doe"),
        new OA\Property(property: "company_name", type: "string", example: "ABC Corp", nullable: true),
        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
        new OA\Property(property: "phone", type: "string", example: "+1234567890"),
        new OA\Property(property: "status", type: "string", enum: ["active", "inactive"], example: "active"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time")
    ]
)]
#[OA\Schema(
    schema: "Vehicle",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "customer_id", type: "integer", example: 1),
        new OA\Property(property: "make", type: "string", example: "Toyota"),
        new OA\Property(property: "model", type: "string", example: "Camry"),
        new OA\Property(property: "year", type: "integer", example: 2022),
        new OA\Property(property: "vin", type: "string", example: "1HGBH41JXMN109186"),
        new OA\Property(property: "license_plate", type: "string", example: "ABC-1234"),
        new OA\Property(property: "color", type: "string", example: "Blue"),
        new OA\Property(property: "current_mileage", type: "integer", example: 15000),
        new OA\Property(property: "status", type: "string", example: "active"),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
#[OA\Schema(
    schema: "JobCard",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "job_card_number", type: "string", example: "JC-2024-001"),
        new OA\Property(property: "vehicle_id", type: "integer", example: 1),
        new OA\Property(property: "customer_id", type: "integer", example: 1),
        new OA\Property(property: "status", type: "string", enum: ["draft", "open", "in_progress", "completed", "closed"], example: "open"),
        new OA\Property(property: "priority", type: "string", enum: ["low", "medium", "high", "urgent"], example: "medium"),
        new OA\Property(property: "estimated_cost", type: "number", format: "decimal", example: 500.00),
        new OA\Property(property: "actual_cost", type: "number", format: "decimal", example: 550.00, nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
#[OA\Schema(
    schema: "Invoice",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "invoice_number", type: "string", example: "INV-2024-001"),
        new OA\Property(property: "customer_id", type: "integer", example: 1),
        new OA\Property(property: "job_card_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["draft", "sent", "paid", "overdue", "cancelled"], example: "sent"),
        new OA\Property(property: "subtotal", type: "number", format: "decimal", example: 500.00),
        new OA\Property(property: "tax", type: "number", format: "decimal", example: 50.00),
        new OA\Property(property: "total", type: "number", format: "decimal", example: 550.00),
        new OA\Property(property: "due_date", type: "string", format: "date"),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
class SwaggerController extends Controller
{
    /**
     * This controller exists solely for OpenAPI documentation annotations.
     * No actual methods are implemented as this is just for Swagger documentation structure.
     */
}
