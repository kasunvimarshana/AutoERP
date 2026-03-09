<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Auth Controller
 *
 * Handles stateless JWT/Passport authentication with multi-guard SSO support.
 * Thin controller - delegates to service layer.
 */
class AuthController extends Controller
{
    /** Default token lifetime in minutes (1 year). Multiplied by 60 to convert to seconds for OAuth response. */
    private const DEFAULT_TOKEN_EXPIRY_MINUTES = 525600;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user in the current tenant context.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $tenantId = $request->input('_tenant_id');
        $data = $request->validated();
        $data['tenant_id'] = $tenantId;
        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepository->create($data);

        // Assign default role
        $user->assignRole('member');

        $token = $user->createToken('api-token')->accessToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'message' => 'User registered successfully.',
        ], 201);
    }

    /**
     * Login and issue an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tenantId = $request->input('_tenant_id');
        $credentials = $request->only(['email', 'password']);

        $user = $this->userRepository->findByEmail($credentials['email'], $tenantId);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
                'error_code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
                'error_code' => 'ACCOUNT_INACTIVE',
            ], 403);
        }

        $token = $user->createToken('api-token')->accessToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('passport.tokens_expire_in', self::DEFAULT_TOKEN_EXPIRY_MINUTES) * 60,
            ],
            'message' => 'Login successful.',
        ]);
    }

    /**
     * Logout and revoke the access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()),
        ]);
    }
}
