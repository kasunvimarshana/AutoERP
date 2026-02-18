<?php

namespace Modules\IAM\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model representing a system user",
 *     required={"id", "display_name", "email_address", "account_status"},
 *     @OA\Property(property="id", type="integer", example=1, description="Unique user identifier"),
 *     @OA\Property(property="display_name", type="string", example="John Doe", description="User's full name"),
 *     @OA\Property(property="email_address", type="string", format="email", example="john.doe@example.com", description="User's email address"),
 *     @OA\Property(property="avatar_url", type="string", format="uri", nullable=true, example="https://example.com/avatar.jpg", description="URL to user's avatar image"),
 *     @OA\Property(property="contact_phone", type="string", nullable=true, example="+1234567890", description="User's phone number"),
 *     @OA\Property(property="user_timezone", type="string", example="UTC", description="User's timezone"),
 *     @OA\Property(property="preferred_locale", type="string", example="en", description="User's preferred language locale"),
 *     @OA\Property(property="account_status", type="string", enum={"active", "inactive", "deleted"}, example="active", description="Current account status"),
 *     @OA\Property(
 *         property="verification_status",
 *         type="object",
 *         @OA\Property(property="is_verified", type="boolean", example=true),
 *         @OA\Property(property="email_confirmed", type="boolean", example=true),
 *         @OA\Property(property="verified_timestamp", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00Z")
 *     ),
 *     @OA\Property(
 *         property="mfa_configuration",
 *         type="object",
 *         @OA\Property(property="enabled", type="boolean", example=false),
 *         @OA\Property(property="has_backup_codes", type="boolean", example=false)
 *     ),
 *     @OA\Property(
 *         property="last_activity",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="login_timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="human_readable", type="string", example="2 hours ago"),
 *         @OA\Property(property="origin_ip", type="string", example="192.168.xxx.xxx")
 *     ),
 *     @OA\Property(
 *         property="tenant_association",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="tenant_id", type="integer", example=1),
 *         @OA\Property(property="tenant_name", type="string", example="Acme Corp")
 *     ),
 *     @OA\Property(property="assigned_roles", type="array", @OA\Items(ref="#/components/schemas/Role")),
 *     @OA\Property(property="direct_permissions", type="array", @OA\Items(ref="#/components/schemas/Permission")),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="email_verified", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="account_created", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="last_updated", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     title="Role",
 *     description="Role model for RBAC system",
 *     required={"id", "role_name", "role_identifier"},
 *     @OA\Property(property="id", type="integer", example=1, description="Unique role identifier"),
 *     @OA\Property(property="role_name", type="string", example="admin", description="Role name"),
 *     @OA\Property(property="role_identifier", type="string", example="web", description="Guard name for the role"),
 *     @OA\Property(property="role_description", type="string", nullable=true, example="Administrator role with full access", description="Role description"),
 *     @OA\Property(property="system_role", type="boolean", example=false, description="Whether this is a system-managed role"),
 *     @OA\Property(property="editable", type="boolean", example=true, description="Whether this role can be edited"),
 *     @OA\Property(
 *         property="hierarchy",
 *         type="object",
 *         @OA\Property(property="has_parent", type="boolean", example=false),
 *         @OA\Property(property="parent_role_id", type="integer", nullable=true, example=null),
 *         @OA\Property(property="parent_role_name", type="string", nullable=true, example=null)
 *     ),
 *     @OA\Property(
 *         property="tenant_scope",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="tenant_id", type="integer", example=1),
 *         @OA\Property(property="tenant_name", type="string", example="Acme Corp")
 *     ),
 *     @OA\Property(property="permission_grants", type="array", @OA\Items(ref="#/components/schemas/Permission")),
 *     @OA\Property(
 *         property="users_with_role",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="count", type="integer", example=5),
 *         @OA\Property(property="users", type="array", @OA\Items(ref="#/components/schemas/User"))
 *     ),
 *     @OA\Property(property="subordinate_roles", type="array", nullable=true, @OA\Items(ref="#/components/schemas/Role")),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         @OA\Property(property="created", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="modified", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     title="Permission",
 *     description="Permission model for fine-grained access control",
 *     required={"id", "permission_name", "guard_context"},
 *     @OA\Property(property="id", type="integer", example=1, description="Unique permission identifier"),
 *     @OA\Property(property="permission_name", type="string", example="user.create", description="Permission name"),
 *     @OA\Property(property="guard_context", type="string", example="web", description="Guard name for the permission"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Allows creating new users", description="Permission description"),
 *     @OA\Property(
 *         property="scope",
 *         type="object",
 *         @OA\Property(property="resource_type", type="string", example="user"),
 *         @OA\Property(property="action_type", type="string", example="create"),
 *         @OA\Property(property="full_scope", type="string", example="user:create")
 *     ),
 *     @OA\Property(property="system_managed", type="boolean", example=false, description="Whether this is a system-managed permission"),
 *     @OA\Property(property="tenant_scoped", type="boolean", example=false, description="Whether this permission is tenant-specific"),
 *     @OA\Property(
 *         property="tenant_reference",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="tenant_id", type="integer", example=1),
 *         @OA\Property(property="tenant_name", type="string", example="Acme Corp")
 *     ),
 *     @OA\Property(
 *         property="roles_granting_permission",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="count", type="integer", example=3),
 *         @OA\Property(property="role_names", type="array", @OA\Items(type="string", example="admin"))
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     title="Login Request",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
 *     @OA\Property(property="password", type="string", format="password", example="SecureP@ss123", description="User's password"),
 *     @OA\Property(property="remember", type="boolean", example=false, description="Remember user session for extended period"),
 *     @OA\Property(property="mfa_code", type="string", minLength=6, maxLength=6, example="123456", description="Multi-factor authentication code (if MFA is enabled)")
 * )
 *
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     title="Register Request",
 *     required={"name", "email", "password", "password_confirmation"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="John Doe", description="User's full name"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
 *     @OA\Property(property="password", type="string", format="password", example="SecureP@ss123", description="User's password (min 8 chars, mixed case, numbers, symbols)"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="SecureP@ss123", description="Password confirmation"),
 *     @OA\Property(property="phone", type="string", maxLength=20, example="+1234567890", description="User's phone number"),
 *     @OA\Property(property="timezone", type="string", example="America/New_York", description="User's timezone"),
 *     @OA\Property(property="locale", type="string", enum={"en", "es", "fr", "de"}, example="en", description="User's preferred language")
 * )
 *
 * @OA\Schema(
 *     schema="ForgotPasswordRequest",
 *     type="object",
 *     title="Forgot Password Request",
 *     required={"email"},
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address")
 * )
 *
 * @OA\Schema(
 *     schema="ResetPasswordRequest",
 *     type="object",
 *     title="Reset Password Request",
 *     required={"email", "password", "password_confirmation", "token"},
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
 *     @OA\Property(property="password", type="string", format="password", example="NewSecureP@ss123", description="New password"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="NewSecureP@ss123", description="Password confirmation"),
 *     @OA\Property(property="token", type="string", example="abc123token", description="Password reset token from email")
 * )
 *
 * @OA\Schema(
 *     schema="StoreUserRequest",
 *     type="object",
 *     title="Create User Request",
 *     required={"name", "email"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="John Doe", description="User's full name"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
 *     @OA\Property(property="phone", type="string", maxLength=20, example="+1234567890", description="User's phone number"),
 *     @OA\Property(property="avatar", type="string", format="uri", example="https://example.com/avatar.jpg", description="URL to user's avatar"),
 *     @OA\Property(property="timezone", type="string", example="America/New_York", description="User's timezone"),
 *     @OA\Property(property="locale", type="string", enum={"en", "es", "fr", "de"}, example="en", description="User's preferred language"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether user account is active"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="string", example="admin"), description="Role names to assign to user"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="user.create"), description="Permission names to assign directly to user")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateUserRequest",
 *     type="object",
 *     title="Update User Request",
 *     @OA\Property(property="name", type="string", maxLength=255, example="John Doe", description="User's full name"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
 *     @OA\Property(property="phone", type="string", maxLength=20, example="+1234567890", description="User's phone number"),
 *     @OA\Property(property="avatar", type="string", format="uri", example="https://example.com/avatar.jpg", description="URL to user's avatar"),
 *     @OA\Property(property="timezone", type="string", example="America/New_York", description="User's timezone"),
 *     @OA\Property(property="locale", type="string", enum={"en", "es", "fr", "de"}, example="en", description="User's preferred language"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether user account is active"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="string", example="admin"), description="Role names to assign to user"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="user.create"), description="Permission names to assign directly to user")
 * )
 *
 * @OA\Schema(
 *     schema="ChangePasswordRequest",
 *     type="object",
 *     title="Change Password Request",
 *     required={"current_password", "password", "password_confirmation"},
 *     @OA\Property(property="current_password", type="string", format="password", example="CurrentP@ss123", description="Current password"),
 *     @OA\Property(property="password", type="string", format="password", example="NewSecureP@ss123", description="New password"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="NewSecureP@ss123", description="Password confirmation")
 * )
 *
 * @OA\Schema(
 *     schema="StoreRoleRequest",
 *     type="object",
 *     title="Create Role Request",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="manager", description="Role name (must be unique)"),
 *     @OA\Property(property="description", type="string", maxLength=500, example="Manager role with elevated permissions", description="Role description"),
 *     @OA\Property(property="parent_id", type="integer", example=1, description="Parent role ID for hierarchy"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="user.view"), description="Permission names to assign to role")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateRoleRequest",
 *     type="object",
 *     title="Update Role Request",
 *     @OA\Property(property="name", type="string", maxLength=255, example="manager", description="Role name"),
 *     @OA\Property(property="description", type="string", maxLength=500, example="Manager role with elevated permissions", description="Role description"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=1, description="Parent role ID for hierarchy"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="user.view"), description="Permission names to assign to role")
 * )
 *
 * @OA\Schema(
 *     schema="StorePermissionRequest",
 *     type="object",
 *     title="Create Permission Request",
 *     required={"resource", "action"},
 *     @OA\Property(property="resource", type="string", maxLength=255, example="user", description="Resource name (e.g., user, role, product)"),
 *     @OA\Property(property="action", type="string", maxLength=255, example="create", description="Action name (e.g., create, read, update, delete)"),
 *     @OA\Property(property="description", type="string", maxLength=500, example="Allows creating new users", description="Permission description")
 * )
 *
 * @OA\Schema(
 *     schema="ApiSuccessResponse",
 *     type="object",
 *     title="Success Response",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation completed successfully"),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     type="object",
 *     title="Error Response",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Operation failed"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     title="Validation Error Response",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\Property(
 *             property="field_name",
 *             type="array",
 *             @OA\Items(type="string", example="The field is required.")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     title="Paginated Response",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *     @OA\Property(
 *         property="links",
 *         type="object",
 *         @OA\Property(property="first", type="string", nullable=true, example="http://api.example.com/resource?page=1"),
 *         @OA\Property(property="last", type="string", nullable=true, example="http://api.example.com/resource?page=10"),
 *         @OA\Property(property="prev", type="string", nullable=true, example="http://api.example.com/resource?page=1"),
 *         @OA\Property(property="next", type="string", nullable=true, example="http://api.example.com/resource?page=3")
 *     ),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer", example=2),
 *         @OA\Property(property="from", type="integer", example=16),
 *         @OA\Property(property="last_page", type="integer", example=10),
 *         @OA\Property(property="path", type="string", example="http://api.example.com/resource"),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="to", type="integer", example=30),
 *         @OA\Property(property="total", type="integer", example=150)
 *     )
 * )
 */
class IAMSchemas
{
    // This class exists only to hold OpenAPI schema definitions
}
