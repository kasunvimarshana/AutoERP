<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="AutoERP API",
 *     description="Enterprise-grade Multi-Tenant ERP System - RESTful API Documentation",
 *     @OA\Contact(
 *         email="kasun@example.com",
 *         name="AutoERP Support Team"
 *     ),
 *     @OA\License(
 *         name="MIT License",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="AutoERP API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum Bearer Token Authentication. Obtain token from /api/auth/login endpoint."
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and session management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="IAM-Auth",
 *     description="Identity and Access Management - Authentication endpoints"
 * )
 *
 * @OA\Tag(
 *     name="IAM-Users",
 *     description="Identity and Access Management - User management operations"
 * )
 *
 * @OA\Tag(
 *     name="IAM-Roles",
 *     description="Identity and Access Management - Role hierarchy and management"
 * )
 *
 * @OA\Tag(
 *     name="IAM-Permissions",
 *     description="Identity and Access Management - Permission management"
 * )
 *
 * @OA\Tag(
 *     name="Users",
 *     description="User profile and management operations"
 * )
 *
 * @OA\Tag(
 *     name="Roles",
 *     description="Role-based access control management"
 * )
 *
 * @OA\Tag(
 *     name="Permissions",
 *     description="Permission management for RBAC system"
 * )
 *
 * @OA\Tag(
 *     name="Health",
 *     description="System health monitoring endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Tenants",
 *     description="Multi-tenant organization management"
 * )
 *
 * @OA\Tag(
 *     name="Configuration",
 *     description="Tenant-specific configuration settings"
 * )
 *
 * @OA\Tag(
 *     name="Audit Logs",
 *     description="System audit trail and activity logging"
 * )
 *
 * @OA\Tag(
 *     name="Metadata",
 *     description="Dynamic metadata for frontend configuration"
 * )
 *
 * @OA\Tag(
 *     name="Sales-Orders",
 *     description="Sales order management - Create and manage customer sales orders"
 * )
 *
 * @OA\Tag(
 *     name="Sales-Customers",
 *     description="Customer relationship management - Customer profiles and account management"
 * )
 *
 * @OA\Tag(
 *     name="Sales-Quotations",
 *     description="Sales quotation management - Create and manage customer quotations"
 * )
 *
 * @OA\Tag(
 *     name="Purchasing-Orders",
 *     description="Purchase order management - Create and manage supplier purchase orders"
 * )
 *
 * @OA\Tag(
 *     name="Purchasing-Suppliers",
 *     description="Supplier/vendor management - Manage supplier relationships and information"
 * )
 *
 * @OA\Tag(
 *     name="Purchasing-GoodsReceipts",
 *     description="Goods receipt management - Record and manage incoming shipments from suppliers"
 * )
 *
 * @OA\Tag(
 *     name="Inventory-Products",
 *     description="Product catalog management - Products, variants, and attributes"
 * )
 *
 * @OA\Tag(
 *     name="Inventory-Warehouses",
 *     description="Warehouse management - Storage facilities and locations"
 * )
 *
 * @OA\Tag(
 *     name="Inventory-Stock",
 *     description="Inventory stock management - Stock levels, movements, and adjustments"
 * )
 */
class OpenApiSpec
{
    // This class exists only to hold OpenAPI annotations
}
