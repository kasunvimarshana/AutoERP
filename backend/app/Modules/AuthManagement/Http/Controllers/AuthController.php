<?php

namespace App\Modules\AuthManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\AuthManagement\Services\AuthService;
use App\Modules\AuthManagement\Http\Requests\RegisterRequest;
use App\Modules\AuthManagement\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AuthController extends BaseController
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    #[OA\Post(
        path: "/api/v1/auth/register",
        summary: "Register a new user account",
        description: "Creates a new user account with the specified details. The user will be associated with a tenant and assigned a role.",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["tenant_id", "name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "tenant_id", type: "integer", example: 1, description: "Tenant ID to associate the user with"),
                    new OA\Property(property: "name", type: "string", example: "John Doe", description: "Full name of the user"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com", description: "Email address"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "Password (min 8 characters)"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123", description: "Password confirmation"),
                    new OA\Property(property: "role", type: "string", example: "user", description: "Role to assign (super_admin, admin, manager, user)", enum: ["super_admin", "admin", "manager", "user"])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User registered successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Registration successful"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "user",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                                        new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                        new OA\Property(property: "tenant_id", type: "integer", example: 1),
                                        new OA\Property(property: "status", type: "string", example: "active"),
                                        new OA\Property(property: "created_at", type: "string", format: "date-time")
                                    ],
                                    type: "object"
                                ),
                                new OA\Property(property: "token", type: "string", example: "1|abcdefghijklmnopqrstuvwxyz")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse"),
            new OA\Response(response: 500, description: "Server error", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            
            return $this->created([
                'user' => $result['user'],
                'token' => $result['token'],
            ], 'Registration successful');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/login",
        summary: "Authenticate user and receive access token",
        description: "Authenticates a user with email and password, returns user details and Bearer token for API access",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@autoerp.com", description: "User email address"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "User password")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Login successful"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "user",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "Super Administrator"),
                                        new OA\Property(property: "email", type: "string", example: "admin@autoerp.com"),
                                        new OA\Property(property: "tenant_id", type: "integer", example: 1),
                                        new OA\Property(property: "role", type: "string", example: "super_admin"),
                                        new OA\Property(property: "status", type: "string", example: "active")
                                    ],
                                    type: "object"
                                ),
                                new OA\Property(property: "token", type: "string", example: "2|abcdefghijklmnopqrstuvwxyz", description: "Bearer token for API authentication")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid credentials", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse")
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());
            
            return $this->success([
                'user' => $result['user'],
                'token' => $result['token'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/logout",
        summary: "Logout and revoke current token",
        description: "Logs out the authenticated user and revokes their current access token",
        security: [["sanctum" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Logout successful"),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 500, description: "Server error", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            return $this->success(null, 'Logout successful');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/auth/me",
        summary: "Get current authenticated user",
        description: "Returns the profile information of the currently authenticated user including roles and permissions",
        security: [["sanctum" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "User profile retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Success"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Super Administrator"),
                                new OA\Property(property: "email", type: "string", example: "admin@autoerp.com"),
                                new OA\Property(property: "tenant_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "active"),
                                new OA\Property(
                                    property: "roles",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "super_admin")
                                ),
                                new OA\Property(
                                    property: "permissions",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "users.create")
                                ),
                                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        try {
            return $this->success($request->user());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/refresh-token",
        summary: "Refresh authentication token",
        description: "Revokes the current token and issues a new one. Useful for extending session duration",
        security: [["sanctum" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Token refreshed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Token refreshed successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "token", type: "string", example: "3|newabcdefghijklmnopqrstuvwxyz")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 500, description: "Server error", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $token = $this->authService->refreshToken($request->user());
            return $this->success(['token' => $token], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/password/change",
        summary: "Change user password",
        description: "Changes the password for the currently authenticated user. Requires current password verification. User must login again after password change",
        security: [["sanctum" => []]],
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["current_password", "new_password", "new_password_confirmation"],
                properties: [
                    new OA\Property(property: "current_password", type: "string", format: "password", example: "oldpassword123", description: "Current password"),
                    new OA\Property(property: "new_password", type: "string", format: "password", example: "newpassword123", description: "New password (min 8 characters)"),
                    new OA\Property(property: "new_password_confirmation", type: "string", format: "password", example: "newpassword123", description: "New password confirmation")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password changed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Password changed successfully. Please login again."),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid current password or validation error", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 401, description: "Unauthenticated", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse")
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $this->authService->changePassword(
                $request->user(),
                $request->input('current_password'),
                $request->input('new_password')
            );

            return $this->success(null, 'Password changed successfully. Please login again.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/password/request-reset",
        summary: "Request password reset",
        description: "Sends a password reset link to the user's email address. For security, always returns success regardless of whether the email exists",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com", description: "Email address to send reset link to")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password reset request processed",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "If an account exists with this email, you will receive password reset instructions."),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse"),
            new OA\Response(response: 500, description: "Server error", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $this->authService->requestPasswordReset($request->input('email'));
            
            return $this->success(null, 'If an account exists with this email, you will receive password reset instructions.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/password/reset",
        summary: "Reset password using token",
        description: "Resets user password using the token received via email from password reset request",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "token", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com", description: "Email address"),
                    new OA\Property(property: "token", type: "string", example: "abcdef123456", description: "Password reset token from email"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "newpassword123", description: "New password (min 8 characters)"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "newpassword123", description: "Password confirmation")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password reset successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Password reset successfully. Please login."),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid or expired reset token", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse"),
            new OA\Response(response: 500, description: "Server error", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $result = $this->authService->resetPassword(
                $request->input('email'),
                $request->input('token'),
                $request->input('password')
            );

            if ($result) {
                return $this->success(null, 'Password reset successfully. Please login.');
            }

            return $this->error('Invalid or expired reset token', 400);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
