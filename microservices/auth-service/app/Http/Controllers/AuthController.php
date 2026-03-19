<?php

namespace App\Http\Controllers;

use App\Services\LoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * AuthController - Main entry point for user authentication.
 * Follows Thin Controller → Service → Repository pattern.
 */
class AuthController extends Controller
{
    protected LoginService $loginService;

    public function __construct(LoginService $loginService)
    {
        $this->loginService = $loginService;
    }

    /**
     * Handle user login.
     * Validates input, passes to LoginService, and returns a resource response.
     */
    public function login(Request $request)
    {
        // 1. Validate request (Metadata-driven validation is handled in higher layers)
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'tenant_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 2. Pass to domain service (DDD logic)
            $response = $this->loginService->authenticate(
                $request->email,
                $request->password,
                $request->tenant_id
            );

            // 3. Return success response (Stateless JWT)
            return response()->json([
                'status' => 'success',
                'message' => 'Authenticated successfully',
                'data' => $response,
            ], 200);

        } catch (\Exception $e) {
            // 4. Handle domain errors with appropriate HTTP status codes
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Handle user logout (Stateless).
     * Since JWT is stateless, logout involves blacklisting the token or simply client-side clearing.
     */
    public function logout(Request $request)
    {
        // In a real scenario, we might publish a 'UserLoggedOut' event to Kafka 
        // to invalidate caches or blacklist the token globally.
        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully (Stateless)',
        ]);
    }
}
