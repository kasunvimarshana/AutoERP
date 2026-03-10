<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role'     => 'sometimes|in:admin,user',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'] ?? 'user',
        ]);

        $userArray = $user->toArray();
        $token        = $this->jwt->generateToken($userArray);
        $refreshToken = $this->jwt->generateRefreshToken($userArray);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data'    => [
                'user'          => $userArray,
                'token'         => $token,
                'refresh_token' => $refreshToken,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $userArray    = $user->toArray();
        $token        = $this->jwt->generateToken($userArray);
        $refreshToken = $this->jwt->generateRefreshToken($userArray);

        return response()->json([
            'success' => true,
            'data'    => [
                'user'          => $userArray,
                'token'         => $token,
                'refresh_token' => $refreshToken,
                'token_type'    => 'bearer',
                'expires_in'    => $this->jwt->getTtl(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Stateless JWT: no server-side session to invalidate.
        // Clients must discard the token on their side.
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token required',
            ], 401);
        }

        try {
            $payload = $this->jwt->validateToken($token);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            return response()->json([
                'success' => false,
                'message' => 'A refresh token is required for this endpoint',
            ], 401);
        }

        $user = User::find($payload['sub']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $userArray    = $user->toArray();
        $newToken        = $this->jwt->generateToken($userArray);
        $newRefreshToken = $this->jwt->generateRefreshToken($userArray);

        return response()->json([
            'success' => true,
            'data'    => [
                'token'         => $newToken,
                'refresh_token' => $newRefreshToken,
                'token_type'    => 'bearer',
                'expires_in'    => $this->jwt->getTtl(),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'success' => true,
            'data'    => ['user' => $user],
        ]);
    }

    public function validate(Request $request): JsonResponse
    {
        $token = $request->input('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
                'data'    => ['valid' => false],
            ], 422);
        }

        try {
            $payload = $this->jwt->validateToken($token);

            if (($payload['type'] ?? '') !== 'access') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token',
                    'data'    => ['valid' => false],
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'valid' => true,
                    'user'  => [
                        'id'    => $payload['sub'],
                        'email' => $payload['email'],
                        'role'  => $payload['role'],
                    ],
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
                'data'    => ['valid' => false],
            ], 401);
        }
    }

    private function extractToken(Request $request): ?string
    {
        $bearer = $request->bearerToken();
        if ($bearer) {
            return $bearer;
        }

        return $request->input('token');
    }
}
