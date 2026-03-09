<?php

namespace App\Services;

use App\Domain\Contracts\AuthServiceInterface;
use App\Domain\Contracts\TenantRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Events\UserLoggedIn;
use App\Domain\Events\UserRegistered;
use App\Domain\Models\AuditLog;
use App\Domain\Models\DeviceToken;
use App\Domain\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function login(
        string $email,
        string $password,
        ?string $tenantId = null,
        ?string $deviceId = null,
        ?string $deviceName = null,
        bool $rememberMe = false
    ): array {
        // Find tenant if provided
        $tenant = $tenantId ? $this->tenantRepository->findOrFail($tenantId) : null;

        if ($tenant && $tenant->status !== 'active') {
            throw ValidationException::withMessages([
                'tenant' => ['The tenant account is not active.'],
            ]);
        }

        // Find user by email + tenant scope
        $user = $this->userRepository->findByEmail($email, $tenantId);

        if ($user === null || !Hash::check($password, $user->password)) {
            // Use same message to prevent user enumeration
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!($user->is_active ?? true)) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Record the login
        $user->recordLogin(request()->ip());

        // Issue token with tenant claims
        $tenantClaims = $tenant ? ['tenant_id' => $tenant->id, 'tenant_slug' => $tenant->subdomain] : [];
        $tokenName    = $deviceName ?? ($deviceId ? "device:{$deviceId}" : 'api-token');
        $tokenResult  = $this->tokenService->createForUser($user, $tokenName, ['*'], $tenantClaims);

        // Track device token if device_id provided
        if ($deviceId && config('app.features.device_tracking', true)) {
            $this->trackDevice($user, $deviceId, $deviceName, $tokenResult, $rememberMe);
        }

        // Fire event
        Event::dispatch(new UserLoggedIn(
            user: $user,
            ipAddress: request()->ip(),
            deviceId: $deviceId,
            tenantId: $tenant?->id,
        ));

        // Audit log
        if (config('app.features.audit_log', true)) {
            AuditLog::record(
                event: 'user.login',
                userId: $user->id,
                tenantId: $tenant?->id ?? $user->tenant_id,
                auditable: $user,
                metadata: ['device_id' => $deviceId, 'ip' => request()->ip()],
            );
        }

        return array_merge($tokenResult, ['user' => $user->load(['tenant', 'roles'])]);
    }

    /**
     * {@inheritdoc}
     */
    public function register(array $data): array
    {
        $tenant = $this->tenantRepository->findOrFail($data['tenant_id']);

        if ($tenant->status !== 'active') {
            throw ValidationException::withMessages([
                'tenant_id' => ['The tenant account is not active.'],
            ]);
        }

        // Check plan limits
        $userCount = $tenant->users()->count();
        if (!$tenant->withinPlanLimit('users', $userCount)) {
            throw ValidationException::withMessages([
                'tenant_id' => ['User limit reached for this tenant plan.'],
            ]);
        }

        DB::beginTransaction();
        try {
            $user = $this->userRepository->create([
                'tenant_id' => $data['tenant_id'],
                'org_id'    => $data['org_id'] ?? null,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'phone'     => $data['phone'] ?? null,
                'timezone'  => $data['timezone'] ?? 'UTC',
                'locale'    => $data['locale'] ?? 'en',
                'is_active' => true,
            ]);

            // Assign default role for new users
            $user->assignRole('viewer');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User registration failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }

        $tokenResult = $this->tokenService->createForUser(
            $user,
            'registration-token',
            ['*'],
            ['tenant_id' => $tenant->id, 'tenant_slug' => $tenant->subdomain]
        );

        Event::dispatch(new UserRegistered(
            user: $user,
            tenantId: $tenant->id,
            ipAddress: request()->ip(),
        ));

        AuditLog::record(
            event: 'user.registered',
            userId: $user->id,
            tenantId: $tenant->id,
            auditable: $user,
        );

        return array_merge($tokenResult, ['user' => $user->load(['tenant', 'roles'])]);
    }

    /**
     * {@inheritdoc}
     */
    public function logout(User $user, ?string $deviceId = null, bool $revokeAll = false): void
    {
        if ($revokeAll) {
            $this->tokenService->revokeAllForUser($user->id);
            $user->deviceTokens()->update(['is_active' => false]);
        } else {
            // Revoke current token
            $currentToken = $user->token();
            if ($currentToken) {
                $currentToken->revoke();
            }

            // Revoke specific device token
            if ($deviceId) {
                $user->deviceTokens()
                     ->where('device_id', $deviceId)
                     ->update(['is_active' => false]);
            }
        }

        AuditLog::record(
            event: 'user.logout',
            userId: $user->id,
            tenantId: $user->tenant_id,
            auditable: $user,
            metadata: ['device_id' => $deviceId, 'revoke_all' => $revokeAll],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken(string $refreshToken): array
    {
        return $this->tokenService->refresh($refreshToken);
    }

    /**
     * {@inheritdoc}
     */
    public function sendPasswordResetLink(string $email, ?string $tenantId = null): void
    {
        // We intentionally don't reveal whether the email exists
        $user = $this->userRepository->findByEmail($email, $tenantId);

        if ($user) {
            Password::sendResetLink(['email' => $email]);

            AuditLog::record(
                event: 'user.password_reset_requested',
                userId: $user->id,
                tenantId: $tenantId ?? $user->tenant_id,
                auditable: $user,
                metadata: ['ip' => request()->ip()],
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetPassword(string $token, string $email, string $password, ?string $tenantId = null): void
    {
        $status = Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all existing tokens on password reset
                $this->tokenService->revokeAllForUser($user->id);

                Event::dispatch(new PasswordReset($user));

                AuditLog::record(
                    event: 'user.password_reset',
                    userId: $user->id,
                    tenantId: $user->tenant_id,
                    auditable: $user,
                    metadata: ['ip' => request()->ip()],
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserFromToken(string $token): ?User
    {
        return $this->tokenService->getUserFromToken($token);
    }

    /**
     * Track device for SSO.
     */
    private function trackDevice(
        User $user,
        string $deviceId,
        ?string $deviceName,
        array $tokenResult,
        bool $rememberMe
    ): void {
        $tokenExpiry   = config('passport.tokens_expire_in');
        $refreshExpiry = config('passport.refresh_tokens_expire_in');

        $tokenDays   = $tokenExpiry instanceof \DateInterval
            ? (int) (new \DateTime())->add($tokenExpiry)->diff(new \DateTime())->days
            : (int) $tokenExpiry;
        $refreshDays = $refreshExpiry instanceof \DateInterval
            ? (int) (new \DateTime())->add($refreshExpiry)->diff(new \DateTime())->days
            : (int) $refreshExpiry;

        $expiresAt = $rememberMe
            ? now()->addDays($refreshDays ?: 30)
            : now()->addDays($tokenDays ?: 15);

        DeviceToken::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            [
                'tenant_id'    => $user->tenant_id,
                'device_name'  => $deviceName,
                'token_id'     => $tokenResult['token_model']?->id ?? null,
                'last_used_at' => now(),
                'last_used_ip' => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'is_active'    => true,
                'expires_at'   => $expiresAt,
            ]
        );
    }
}
