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
    description: <<<'DESC'
KVAutoERP – a modular, multi-tenant ERP REST API built on Laravel 12 with Passport OAuth2 authentication.

## Versioning Strategy
This API uses **semantic versioning** (semver). The current stable release is `1.0.0`.

- **Minor/patch releases** (e.g. `1.0.x`, `1.x.0`) are backward-compatible and do not break existing
  integrations. New fields, optional parameters, and additional endpoints may be added.
- **Major releases** (e.g. `2.0.0`) introduce breaking changes. When a new major version is published,
  a new URL path prefix (e.g. `/api/v2/`) will be introduced so that both versions remain accessible
  simultaneously during the migration window. The old version will be deprecated first and removed
  after the announced sunset date.
- **Deprecation notices** are communicated via an `X-API-Deprecated` response header and
  documented in the changelog.

## Authentication
All protected endpoints require a Bearer token issued by `POST /api/auth/login` or
`POST /api/auth/register`. Pass it in the `Authorization: Bearer <token>` request header.
Use the **Authorize** button above to set your token once and apply it to all secured endpoints.

## Cross-Origin Resource Sharing (CORS)
The API is CORS-enabled. Allowed origins, methods, and headers are configured via environment
variables (`CORS_ALLOWED_ORIGINS`, `CORS_ALLOWED_METHODS`, `CORS_ALLOWED_HEADERS`). See the
`.env.example` file for all available CORS settings.
DESC,
    title: 'KVAutoERP API',
    contact: new OA\Contact(name: 'KVAutoERP Support', email: 'support@kvautoerp.local'),
    license: new OA\License(name: 'MIT', url: 'https://opensource.org/licenses/MIT'),
)]

// ── Server ────────────────────────────────────────────────────────────────────
// L5_SWAGGER_CONST_HOST is defined by l5-swagger before scanning (see config/l5-swagger.php
// → defaults.constants). It reads the L5_SWAGGER_CONST_HOST env variable and falls back to
// APP_URL, so the generated spec always reflects the correct environment-specific base URL.
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'API Server')]

// ── Security Scheme ───────────────────────────────────────────────────────────
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Enter the Passport access token returned by POST /api/auth/login or POST /api/auth/register.',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]

// ── Tags ─────────────────────────────────────────────────────────────────────
#[OA\Tag(name: 'Health',             description: 'Application health check – liveness and readiness probes')]
#[OA\Tag(name: 'Auth',               description: 'Authentication – register, login, logout, token refresh, SSO, and password reset')]
#[OA\Tag(name: 'Users',              description: 'User management – CRUD, role assignment, preference updates')]
#[OA\Tag(name: 'Roles',              description: 'Role management – create, list, view, delete, sync permissions')]
#[OA\Tag(name: 'Permissions',        description: 'Permission management – create, list, view, delete')]
#[OA\Tag(name: 'User Attachments',   description: 'User file attachments – upload, list, delete, serve')]
#[OA\Tag(name: 'Tenants',            description: 'Tenant management – CRUD, config update, domain lookup')]
#[OA\Tag(name: 'Tenant Attachments', description: 'Tenant file attachments – upload, list, delete, serve')]
#[OA\Tag(name: 'Organization Units', description: 'Hierarchical organizational unit management – CRUD, tree view, move')]
#[OA\Tag(name: 'OrgUnit Attachments',description: 'Organization-unit file attachments – upload, list, delete, serve')]
#[OA\Tag(name: 'Products',           description: 'Product management – CRUD with flexible attributes and optional image handling')]
#[OA\Tag(name: 'Product Images',     description: 'Product image management – upload, list, delete, serve')]
#[OA\Tag(name: 'Brands',             description: 'Brand management – CRUD with flexible attributes and optional logo handling')]
#[OA\Tag(name: 'Brand Logo',         description: 'Brand logo management – upload, delete, serve')]
#[OA\Tag(name: 'Categories',         description: 'Category management – CRUD with hierarchical nesting, flexible attributes, and optional image handling')]
#[OA\Tag(name: 'Category Images',    description: 'Category image management – upload, delete, serve')]
#[OA\Tag(name: 'Accounts',           description: 'Account management – Chart of Accounts CRUD with hierarchical structure and flexible attributes')]
#[OA\Tag(name: 'Suppliers',          description: 'Supplier management – CRUD with optional user login access, flexible attributes and multi-tenant support')]

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
        new OA\Property(property: 'language',      type: 'string', nullable: true, example: 'en', enum: ['en', 'es', 'fr', 'de']),
        new OA\Property(property: 'timezone',      type: 'string', nullable: true, example: 'UTC'),
        new OA\Property(property: 'notifications', type: 'array',  nullable: true, items: new OA\Items(type: 'string'), example: []),
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

// ── Tenant Config Schemas ─────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'TenantConfigObject',
    type: 'object',
    required: ['id', 'database_config', 'feature_flags', 'api_keys', 'active', 'updated_at'],
    properties: [
        new OA\Property(property: 'id',              type: 'integer', example: 1),
        new OA\Property(property: 'database_config', ref: '#/components/schemas/DatabaseConfigObject'),
        new OA\Property(property: 'mail_config',     type: 'object',  nullable: true, example: ['host' => 'smtp.example.com']),
        new OA\Property(property: 'cache_config',    type: 'object',  nullable: true, example: ['driver' => 'redis']),
        new OA\Property(property: 'queue_config',    type: 'object',  nullable: true, example: ['driver' => 'database']),
        new OA\Property(property: 'feature_flags',   type: 'object',  example: ['billing' => true, 'reports' => false]),
        new OA\Property(property: 'api_keys',        type: 'object',  example: []),
        new OA\Property(property: 'active',          type: 'boolean', example: true),
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

// ── Product Schemas ───────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'MoneyObject',
    type: 'object',
    required: ['amount', 'currency'],
    properties: [
        new OA\Property(property: 'amount',   type: 'number',  format: 'float', example: 29.99),
        new OA\Property(property: 'currency', type: 'string',  example: 'USD'),
    ],
)]
#[OA\Schema(
    schema: 'ProductImageObject',
    type: 'object',
    required: ['id', 'uuid', 'product_id', 'name', 'mime_type', 'size'],
    properties: [
        new OA\Property(property: 'id',         type: 'integer', example: 1),
        new OA\Property(property: 'uuid',       type: 'string',  format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'name',       type: 'string',  example: 'product-front.jpg'),
        new OA\Property(property: 'file_path',  type: 'string',  example: 'products/1/product-front.jpg'),
        new OA\Property(property: 'mime_type',  type: 'string',  example: 'image/jpeg'),
        new OA\Property(property: 'size',       type: 'integer', example: 204800),
        new OA\Property(property: 'sort_order', type: 'integer', example: 0),
        new OA\Property(property: 'is_primary', type: 'boolean', example: true),
        new OA\Property(property: 'metadata',   type: 'object',  nullable: true),
        new OA\Property(property: 'created_at', type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string',  format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'ProductObject',
    type: 'object',
    required: ['id', 'tenant_id', 'sku', 'name', 'price', 'status'],
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
        new OA\Property(property: 'sku',         type: 'string',  example: 'PROD-001'),
        new OA\Property(property: 'name',        type: 'string',  example: 'Widget Pro'),
        new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'A high quality widget'),
        new OA\Property(property: 'price',       ref: '#/components/schemas/MoneyObject'),
        new OA\Property(property: 'category',    type: 'string',  nullable: true, example: 'Widgets'),
        new OA\Property(property: 'status',      type: 'string',  enum: ['active', 'inactive', 'draft'], example: 'active'),
        new OA\Property(property: 'attributes',  type: 'object',  nullable: true, example: ['color' => 'red', 'weight' => '0.5kg']),
        new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
        new OA\Property(property: 'images',      type: 'array',   items: new OA\Items(ref: '#/components/schemas/ProductImageObject')),
        new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string',  format: 'date-time'),
    ],
)]

// ── Brand Schemas ─────────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'BrandLogoObject',
    type: 'object',
    required: ['id', 'uuid', 'brand_id', 'name', 'mime_type', 'size'],
    properties: [
        new OA\Property(property: 'id',         type: 'integer', example: 1),
        new OA\Property(property: 'uuid',       type: 'string',  format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'brand_id',   type: 'integer', example: 1),
        new OA\Property(property: 'name',       type: 'string',  example: 'acme-logo.png'),
        new OA\Property(property: 'file_path',  type: 'string',  example: 'brands/1/acme-logo.png'),
        new OA\Property(property: 'mime_type',  type: 'string',  example: 'image/png'),
        new OA\Property(property: 'size',       type: 'integer', example: 102400),
        new OA\Property(property: 'metadata',   type: 'object',  nullable: true),
        new OA\Property(property: 'created_at', type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string',  format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'BrandObject',
    type: 'object',
    required: ['id', 'tenant_id', 'name', 'slug', 'status'],
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
        new OA\Property(property: 'name',        type: 'string',  example: 'Acme Brand'),
        new OA\Property(property: 'slug',        type: 'string',  example: 'acme-brand'),
        new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'A well-known brand'),
        new OA\Property(property: 'website',     type: 'string',  nullable: true, example: 'https://acme.example.com'),
        new OA\Property(property: 'status',      type: 'string',  enum: ['active', 'inactive', 'draft'], example: 'active'),
        new OA\Property(property: 'attributes',  type: 'object',  nullable: true, example: ['country' => 'US']),
        new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
        new OA\Property(property: 'logo',        ref: '#/components/schemas/BrandLogoObject', nullable: true),
        new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string',  format: 'date-time'),
    ],
)]

// ── Category Schemas ──────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'CategoryImageObject',
    type: 'object',
    required: ['id', 'uuid', 'category_id', 'name', 'mime_type', 'size'],
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'uuid',        type: 'string',  format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'category_id', type: 'integer', example: 1),
        new OA\Property(property: 'name',        type: 'string',  example: 'electronics.png'),
        new OA\Property(property: 'file_path',   type: 'string',  example: 'categories/1/electronics.png'),
        new OA\Property(property: 'mime_type',   type: 'string',  example: 'image/png'),
        new OA\Property(property: 'size',        type: 'integer', example: 102400),
        new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
        new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string',  format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'CategoryObject',
    type: 'object',
    required: ['id', 'tenant_id', 'name', 'slug', 'status'],
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
        new OA\Property(property: 'name',        type: 'string',  example: 'Electronics'),
        new OA\Property(property: 'slug',        type: 'string',  example: 'electronics'),
        new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'Electronic products'),
        new OA\Property(property: 'parent_id',   type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'depth',       type: 'integer', example: 0),
        new OA\Property(property: 'path',        type: 'string',  example: 'electronics'),
        new OA\Property(property: 'status',      type: 'string',  enum: ['active', 'inactive', 'draft'], example: 'active'),
        new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
        new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
        new OA\Property(property: 'image',       ref: '#/components/schemas/CategoryImageObject', nullable: true),
        new OA\Property(property: 'children',    type: 'array',   items: new OA\Items(ref: '#/components/schemas/CategoryObject'), nullable: true),
        new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string',  format: 'date-time'),
    ],
)]

// ── Account Schemas ───────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'AccountObject',
    type: 'object',
    required: ['id', 'tenant_id', 'code', 'name', 'type', 'currency', 'balance', 'status'],
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
        new OA\Property(property: 'code',        type: 'string',  example: '1000'),
        new OA\Property(property: 'name',        type: 'string',  example: 'Cash'),
        new OA\Property(property: 'type',        type: 'string',  enum: ['asset', 'liability', 'equity', 'income', 'expense'], example: 'asset'),
        new OA\Property(property: 'subtype',     type: 'string',  nullable: true, example: 'current_asset'),
        new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'Cash on hand and in bank'),
        new OA\Property(property: 'currency',    type: 'string',  example: 'USD'),
        new OA\Property(property: 'balance',     type: 'number',  format: 'float', example: 0.00),
        new OA\Property(property: 'is_system',   type: 'boolean', example: false),
        new OA\Property(property: 'parent_id',   type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'status',      type: 'string',  enum: ['active', 'inactive'], example: 'active'),
        new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
        new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
        new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string',  format: 'date-time'),
    ],
)]

// ── Supplier Schemas ──────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'SupplierObject',
    type: 'object',
    properties: [
        new OA\Property(property: 'id',             type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',      type: 'integer', example: 1),
        new OA\Property(property: 'user_id',        type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'name',           type: 'string',  example: 'Acme Supplies Ltd'),
        new OA\Property(property: 'code',           type: 'string',  example: 'SUP-001'),
        new OA\Property(property: 'email',          type: 'string',  nullable: true, example: 'contact@acme.example.com'),
        new OA\Property(property: 'phone',          type: 'string',  nullable: true, example: '+1-555-0100'),
        new OA\Property(property: 'address',        type: 'object',  nullable: true),
        new OA\Property(property: 'contact_person', type: 'object',  nullable: true),
        new OA\Property(property: 'payment_terms',  type: 'string',  nullable: true, example: 'net30'),
        new OA\Property(property: 'currency',       type: 'string',  example: 'USD'),
        new OA\Property(property: 'tax_number',     type: 'string',  nullable: true, example: 'TAX-123456'),
        new OA\Property(property: 'status',         type: 'string',  example: 'active'),
        new OA\Property(property: 'type',           type: 'string',  example: 'manufacturer'),
        new OA\Property(property: 'attributes',     type: 'object',  nullable: true),
        new OA\Property(property: 'metadata',       type: 'object',  nullable: true),
        new OA\Property(property: 'has_user_access',type: 'boolean', example: false),
        new OA\Property(property: 'created_at',     type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',     type: 'string',  format: 'date-time'),
    ],
)]

// ── Customer Schemas ──────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'CustomerObject',
    type: 'object',
    properties: [
        new OA\Property(property: 'id',               type: 'integer', example: 1),
        new OA\Property(property: 'tenant_id',        type: 'integer', example: 1),
        new OA\Property(property: 'user_id',          type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'name',             type: 'string',  example: 'Jane Smith'),
        new OA\Property(property: 'code',             type: 'string',  example: 'CUST-001'),
        new OA\Property(property: 'email',            type: 'string',  nullable: true, example: 'jane@example.com'),
        new OA\Property(property: 'phone',            type: 'string',  nullable: true, example: '+1-555-0100'),
        new OA\Property(property: 'billing_address',  type: 'object',  nullable: true),
        new OA\Property(property: 'shipping_address', type: 'object',  nullable: true),
        new OA\Property(property: 'date_of_birth',    type: 'string',  nullable: true, example: '1990-01-15'),
        new OA\Property(property: 'loyalty_tier',     type: 'string',  nullable: true, example: 'gold'),
        new OA\Property(property: 'credit_limit',     type: 'number',  nullable: true, example: 5000.00),
        new OA\Property(property: 'payment_terms',    type: 'string',  nullable: true, example: 'net30'),
        new OA\Property(property: 'currency',         type: 'string',  example: 'USD'),
        new OA\Property(property: 'tax_number',       type: 'string',  nullable: true, example: 'TAX-123456'),
        new OA\Property(property: 'status',           type: 'string',  example: 'active'),
        new OA\Property(property: 'type',             type: 'string',  example: 'retail'),
        new OA\Property(property: 'attributes',       type: 'object',  nullable: true),
        new OA\Property(property: 'metadata',         type: 'object',  nullable: true),
        new OA\Property(property: 'has_user_access',  type: 'boolean', example: false),
        new OA\Property(property: 'created_at',       type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',       type: 'string',  format: 'date-time'),
    ],
)]

class OpenApiSpec
{
    // This class exists solely to carry global OpenAPI attributes.
    // It has no runtime behaviour.
}
