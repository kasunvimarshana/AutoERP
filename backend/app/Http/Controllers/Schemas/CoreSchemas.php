<?php

namespace App\Http\Controllers\Schemas;

/**
 * @OA\Schema(
 *     schema="TenantResource",
 *     type="object",
 *     title="Tenant Resource",
 *     description="Tenant resource representation",
 *     @OA\Property(property="id", type="integer", example=1, description="Internal database ID"),
 *     @OA\Property(property="public_identifier", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Public UUID identifier"),
 *     @OA\Property(property="organization_name", type="string", example="Acme Corporation", description="Tenant organization name"),
 *     @OA\Property(property="primary_domain", type="string", example="acme.example.com", description="Primary domain for tenant"),
 *     @OA\Property(
 *         property="subscription_details",
 *         type="object",
 *         @OA\Property(property="plan_name", type="string", example="enterprise"),
 *         @OA\Property(property="subscription_active", type="boolean", example=true),
 *         @OA\Property(property="expires_at", type="string", format="date-time", example="2024-12-31T23:59:59Z"),
 *         @OA\Property(property="days_remaining", type="integer", example=180)
 *     ),
 *     @OA\Property(property="account_status", type="string", enum={"active", "suspended", "inactive"}, example="active"),
 *     @OA\Property(
 *         property="configuration",
 *         type="object",
 *         description="Tenant-specific settings (visible to users with view_settings permission)",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="trial_information",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="trial_active", type="boolean", example=true),
 *         @OA\Property(property="trial_expires", type="string", format="date-time", example="2024-02-28T23:59:59Z"),
 *         @OA\Property(property="trial_days_left", type="integer", example=14)
 *     ),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="onboarded", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *         @OA\Property(property="last_modified", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="deleted", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AuditLogResource",
 *     type="object",
 *     title="Audit Log Resource",
 *     description="Audit log entry representation",
 *     @OA\Property(property="id", type="integer", example=12345, description="Audit log ID"),
 *     @OA\Property(property="audit_event", type="string", enum={"created", "updated", "deleted", "restored", "viewed"}, example="updated", description="Event type"),
 *     @OA\Property(
 *         property="entity_reference",
 *         type="object",
 *         @OA\Property(property="entity_type", type="string", example="product", description="Human-readable entity type"),
 *         @OA\Property(property="entity_id", type="integer", example=456, description="Entity ID"),
 *         @OA\Property(property="entity_class", type="string", example="Modules\\Inventory\\Models\\Product", description="Full entity class name")
 *     ),
 *     @OA\Property(
 *         property="actor_information",
 *         type="object",
 *         @OA\Property(property="user_id", type="integer", example=10),
 *         @OA\Property(property="user_type", type="string", example="App\\Models\\User"),
 *         @OA\Property(property="tenant_id", type="integer", example=5)
 *     ),
 *     @OA\Property(
 *         property="change_summary",
 *         type="object",
 *         @OA\Property(property="has_modifications", type="boolean", example=true),
 *         @OA\Property(
 *             property="fields_changed",
 *             type="array",
 *             @OA\Items(type="string"),
 *             example={"name", "price", "status"}
 *         ),
 *         @OA\Property(
 *             property="modifications",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="field_name", type="string", example="price"),
 *                 @OA\Property(property="previous_value", oneOf={@OA\Schema(type="string"), @OA\Schema(type="number"), @OA\Schema(type="boolean")}, example=99.99),
 *                 @OA\Property(property="updated_value", oneOf={@OA\Schema(type="string"), @OA\Schema(type="number"), @OA\Schema(type="boolean")}, example=89.99),
 *                 @OA\Property(property="value_changed", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *     @OA\Property(
 *         property="request_context",
 *         type="object",
 *         @OA\Property(property="request_url", type="string", example="/api/inventory/products/123"),
 *         @OA\Property(property="origin_ip", type="string", example="192.168.**.***", description="Masked IP address for privacy"),
 *         @OA\Property(property="client_agent", type="string", example="Mozilla/5.0...")
 *     ),
 *     @OA\Property(
 *         property="categorization",
 *         type="array",
 *         @OA\Items(type="string"),
 *         example={"inventory", "product_management"}
 *     ),
 *     @OA\Property(property="audit_timestamp", type="string", format="date-time", example="2024-01-15T14:30:00Z"),
 *     @OA\Property(property="human_readable_time", type="string", example="2 hours ago")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Error Response",
 *     description="Standard error response structure",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Optional validation errors or additional error details",
 *         nullable=true,
 *         example={"field_name": {"The field name is required."}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     title="Success Response",
 *     description="Standard success response structure",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(
 *         property="data",
 *         description="Response data (structure varies by endpoint)",
 *         nullable=true
 *     )
 * )
 */
class CoreSchemas
{
    // This class exists only to hold OpenAPI schema annotations
}
