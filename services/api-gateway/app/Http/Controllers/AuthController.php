<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\TokenRepository;

class AuthController extends Controller
{
    public function __construct(
        private readonly TokenRepository $tokenRepository
    ) {}

    // -------------------------------------------------------------------------
    // Register
    // -------------------------------------------------------------------------

    /**
     * Create a new tenant + admin user, then issue a Passport token.
     *
     * POST /auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data): JsonResponse {
            $tenant = Tenant::create([
                'name'      => $data['tenant_name'],
                'domain'    => $data['domain'] ?? Str::slug($data['tenant_name']) . '.' . config('app.domain', 'localhost'),
                'settings'  => [],
                'is_active' => true,
            ]);

            // Enforce per-tenant email uniqueness (new tenant → no existing users, safe).
            // This guard is primarily useful if register() is ever reused for sub-users.
            $emailTaken = User::where('tenant_id', $tenant->id)
                ->where('email', $data['email'])
                ->exists();

            if ($emailTaken) {
                return response()->json([
                    'message' => 'An account with this email already exists in this tenant.',
                ], 422);
            }

            /** @var User $user */
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'role'      => 'admin',
                'is_active' => true,
            ]);

            $user->assignRole('admin');

            $token = $user->createToken(
                'api-gateway',
                ['*'],
                now()->addDays(config('passport.token_expire_days', 7))
            );

            return response()->json([
                'message'      => 'Registration successful.',
                'access_token' => $token->accessToken,
                'token_type'   => 'Bearer',
                'expires_at'   => $token->token->expires_at,
                'user'         => $user->toApiArray(),
                'tenant'       => $tenant->toApiArray(),
            ], 201);
        });
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    /**
     * Validate credentials and issue a Passport token with role-based scopes.
     *
     * POST /auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Account is disabled.'], 403);
        }

        if (! $user->tenant?->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Tenant is disabled.'], 403);
        }

        // Derive scopes from the user's Spatie roles/permissions.
        $scopes = $this->buildScopes($user);

        $token = $user->createToken(
            'api-gateway',
            $scopes,
            now()->addDays(config('passport.token_expire_days', 7))
        );

        return response()->json([
            'access_token' => $token->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $token->token->expires_at,
            'scopes'       => $scopes,
            'user'         => $user->toApiArray(),
            'tenant'       => $user->tenant?->toApiArray(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    /**
     * Revoke the current access token.
     *
     * POST /auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var \Laravel\Passport\Token $token */
        $token = $request->user()->token();
        $token->revoke();

        return response()->json(['message' => 'Successfully logged out.']);
    }

    // -------------------------------------------------------------------------
    // Refresh
    // -------------------------------------------------------------------------

    /**
     * Revoke the current token and issue a fresh one.
     *
     * POST /auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Revoke the current token.
        $user->token()->revoke();

        $scopes = $this->buildScopes($user);

        $token = $user->createToken(
            'api-gateway',
            $scopes,
            now()->addDays(config('passport.token_expire_days', 7))
        );

        return response()->json([
            'access_token' => $token->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $token->token->expires_at,
            'scopes'       => $scopes,
        ]);
    }

    // -------------------------------------------------------------------------
    // Me
    // -------------------------------------------------------------------------

    /**
     * Return the authenticated user's profile, roles, and permissions.
     *
     * GET /auth/me
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('tenant');

        return response()->json([
            'user'   => $user->toApiArray(),
            'tenant' => $user->tenant?->toApiArray(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build Passport token scopes from the user's Spatie roles.
     *
     * @return list<string>
     */
    private function buildScopes(User $user): array
    {
        if ($user->hasRole('admin')) {
            return ['*'];
        }

        $scopes = ['read'];

        if ($user->hasRole('manager')) {
            $scopes[] = 'write';
        }

        if ($user->hasPermissionTo('delete-resources')) {
            $scopes[] = 'delete';
        }

        return $scopes;
    }
}
