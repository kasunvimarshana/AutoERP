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
 */
class OpenApiController extends Controller
{
    // This class exists solely for OpenAPI annotations
}
