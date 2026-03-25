<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\ApiDoc;

use OpenApi\Attributes as OA;

/**
 * Global OpenAPI 3.0 specification entry-point.
 *
 * This class carries the top-level Info, Server, SecurityScheme, Tag, and
 * reusable Schema attributes that apply to the entire KVAutoERP API.
 * The l5-swagger scanner picks them up automatically.
 */

// ── Info ──────────────────────────────────────────────────────────────────────
#[OA\Info(
    version: '1.0.0',
    description: 'KVAutoERP – a modular, multi-tenant ERP REST API built on Laravel 12 with Passport OAuth2 authentication.',
    title: 'KVAutoERP API',
    contact: new OA\Contact(name: 'KVAutoERP Support', email: 'support@kvautoerp.local'),
    license: new OA\License(name: 'MIT', url: 'https://opensource.org/licenses/MIT'),
)]

// ── Server ────────────────────────────────────────────────────────────────────
#[OA\Server(url: 'http://localhost', description: 'API Server')]

// ── Security Scheme ───────────────────────────────────────────────────────────
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Enter the Passport access token returned by POST /api/auth/login or POST /api/auth/register.',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]

// ── Tags ─────────────────────────────────────────────────────────────────────
#[OA\Tag(name: 'Auth',               description: 'Authentication – register, login, logout, token refresh, SSO, and password reset')]
#[OA\Tag(name: 'Users',              description: 'User management – CRUD, role assignment, preference updates')]
#[OA\Tag(name: 'Roles',              description: 'Role management – create, list, view, delete, sync permissions')]
#[OA\Tag(name: 'Permissions',        description: 'Permission management – create, list, view, delete')]
#[OA\Tag(name: 'User Attachments',   description: 'User file attachments – upload, list, delete, serve')]
#[OA\Tag(name: 'Tenants',            description: 'Tenant management – CRUD, config update, domain lookup')]
#[OA\Tag(name: 'Tenant Attachments', description: 'Tenant file attachments – upload, list, delete, serve')]
#[OA\Tag(name: 'Organization Units', description: 'Hierarchical organizational unit management – CRUD, tree view, move')]
#[OA\Tag(name: 'OrgUnit Attachments',description: 'Organization-unit file attachments – upload, list, delete, serve')]

// ── Reusable Error Schemas ────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data is invalid.'),
    ],
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    type: 'object',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data is invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            example: ['email' => ['The email field is required.']],
        ),
    ],
)]
#[OA\Schema(
    schema: 'MessageResponse',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully.'),
    ],
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from',         type: 'integer', example: 1),
        new OA\Property(property: 'last_page',    type: 'integer', example: 5),
        new OA\Property(property: 'per_page',     type: 'integer', example: 15),
        new OA\Property(property: 'to',           type: 'integer', example: 15),
        new OA\Property(property: 'total',        type: 'integer', example: 72),
    ],
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    type: 'object',
    properties: [
        new OA\Property(property: 'first', type: 'string', nullable: true, example: 'http://localhost/api/users?page=1'),
        new OA\Property(property: 'last',  type: 'string', nullable: true, example: 'http://localhost/api/users?page=5'),
        new OA\Property(property: 'prev',  type: 'string', nullable: true),
        new OA\Property(property: 'next',  type: 'string', nullable: true),
    ],
)]

// ── Auth Schemas ──────────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'AuthTokenResponse',
    type: 'object',
    required: ['access_token', 'token_type', 'expires_in'],
    properties: [
        new OA\Property(property: 'access_token',  type: 'string',  example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9…'),
        new OA\Property(property: 'token_type',    type: 'string',  example: 'Bearer'),
        new OA\Property(property: 'expires_in',    type: 'integer', example: 1296000),
        new OA\Property(property: 'refresh_token', type: 'string',  nullable: true),
        new OA\Property(property: 'scopes',        type: 'array',   items: new OA\Items(type: 'string')),
    ],
)]

// ── User Schemas ──────────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'AddressObject',
    type: 'object',
    properties: [
        new OA\Property(property: 'street',  type: 'string', nullable: true, example: '123 Main St'),
        new OA\Property(property: 'city',    type: 'string', nullable: true, example: 'Springfield'),
        new OA\Property(property: 'state',   type: 'string', nullable: true, example: 'IL'),
        new OA\Property(property: 'zip',     type: 'string', nullable: true, example: '62701'),
        new OA\Property(property: 'country', type: 'string', nullable: true, example: 'US'),
    ],
)]
#[OA\Schema(
    schema: 'UserPreferencesObject',
    type: 'object',
    properties: [
        new OA\Property(property: 'locale',   type: 'string', nullable: true, example: 'en'),
        new OA\Property(property: 'timezone', type: 'string', nullable: true, example: 'UTC'),
        new OA\Property(property: 'theme',    type: 'string', nullable: true, example: 'light'),
    ],
)]
#[OA\Schema(
    schema: 'PermissionObject',
    type: 'object',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id',        type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'name',      type: 'string',  example: 'users.view'),
    ],
)]
#[OA\Schema(
    schema: 'RoleObject',
    type: 'object',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',   type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'name',        type: 'string',  example: 'admin'),
        new OA\Property(property: 'permissions', type: 'array',   items: new OA\Items(ref: '#/components/schemas/PermissionObject')),
    ],
)]
#[OA\Schema(
    schema: 'UserObject',
    type: 'object',
    required: ['id', 'email', 'first_name', 'last_name', 'active'],
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',   type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'email',       type: 'string',  format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'first_name',  type: 'string',  example: 'John'),
        new OA\Property(property: 'last_name',   type: 'string',  example: 'Doe'),
        new OA\Property(property: 'full_name',   type: 'string',  example: 'John Doe'),
        new OA\Property(property: 'phone',       type: 'string',  nullable: true, example: '+1-555-0100'),
        new OA\Property(property: 'address',     ref: '#/components/schemas/AddressObject', nullable: true),
        new OA\Property(property: 'preferences', ref: '#/components/schemas/UserPreferencesObject'),
        new OA\Property(property: 'active',      type: 'boolean', example: true),
        new OA\Property(property: 'roles',       type: 'array',   items: new OA\Items(ref: '#/components/schemas/RoleObject')),
        new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string',  format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'AttachmentObject',
    type: 'object',
    required: ['id', 'uuid', 'filename', 'mime_type', 'size', 'url'],
    properties: [
        new OA\Property(property: 'id',         type: 'integer', example: 1),
        new OA\Property(property: 'uuid',       type: 'string',  format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'filename',   type: 'string',  example: 'profile.jpg'),
        new OA\Property(property: 'mime_type',  type: 'string',  example: 'image/jpeg'),
        new OA\Property(property: 'size',       type: 'integer', example: 204800),
        new OA\Property(property: 'type',       type: 'string',  nullable: true, example: 'profile_picture'),
        new OA\Property(property: 'url',        type: 'string',  example: 'http://localhost/storage/user-attachments/550e…'),
        new OA\Property(property: 'created_at', type: 'string',  format: 'date-time'),
    ],
)]

// ── Tenant Schemas ────────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'DatabaseConfigObject',
    type: 'object',
    properties: [
        new OA\Property(property: 'driver',   type: 'string',  example: 'mysql'),
        new OA\Property(property: 'host',     type: 'string',  example: '127.0.0.1'),
        new OA\Property(property: 'port',     type: 'integer', example: 3306),
        new OA\Property(property: 'database', type: 'string',  example: 'tenant_db'),
        new OA\Property(property: 'username', type: 'string',  example: 'db_user'),
        new OA\Property(property: 'password', type: 'string',  example: 'secret'),
    ],
)]
#[OA\Schema(
    schema: 'TenantObject',
    type: 'object',
    required: ['id', 'name', 'domain', 'active'],
    properties: [
        new OA\Property(property: 'id',              type: 'integer', example: 1),
        new OA\Property(property: 'name',            type: 'string',  example: 'Acme Corp'),
        new OA\Property(property: 'domain',          type: 'string',  example: 'acme.example.com'),
        new OA\Property(property: 'logo_url',        type: 'string',  nullable: true),
        new OA\Property(property: 'database_config', ref: '#/components/schemas/DatabaseConfigObject'),
        new OA\Property(property: 'feature_flags',   type: 'object',  example: ['billing' => true, 'reports' => false]),
        new OA\Property(property: 'api_keys',        type: 'object',  example: []),
        new OA\Property(property: 'active',          type: 'boolean', example: true),
        new OA\Property(property: 'created_at',      type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',      type: 'string',  format: 'date-time'),
    ],
)]

// ── Organization Unit Schemas ─────────────────────────────────────────────────
#[OA\Schema(
    schema: 'OrganizationUnitObject',
    type: 'object',
    required: ['id', 'tenant_id', 'name', 'code'],
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
        new OA\Property(property: 'name',        type: 'string',  example: 'Engineering'),
        new OA\Property(property: 'code',        type: 'string',  example: 'ENG'),
        new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'Core engineering team'),
        new OA\Property(property: 'metadata',    type: 'object',  example: []),
        new OA\Property(property: 'parent_id',   type: 'integer', nullable: true),
        new OA\Property(property: 'children',    type: 'array',   items: new OA\Items(ref: '#/components/schemas/OrganizationUnitObject')),
        new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string',  format: 'date-time'),
    ],
)]

class OpenApiSpec
{
    // This class exists solely to carry global OpenAPI attributes.
    // It has no runtime behaviour.
}
