<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Audit\Services\AuditService;
use Modules\Auth\Exceptions\InvalidCredentialsException;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserDevice;
use Modules\Auth\Repositories\UserDeviceRepository;
use Modules\Auth\Repositories\UserRepository;
use Modules\Core\Exceptions\AuthorizationException;
use Modules\Tenant\Services\TenantContext;

/**
 * AuthenticationService
 *
 * Handles authentication business logic including login, logout, token refresh,
 * password verification, device registration and tracking.
 */
class AuthenticationService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected JwtTokenService $jwtTokenService,
        protected AuditService $auditService,
        protected TenantContext $tenantContext,
        protected UserDeviceRepository $userDeviceRepository
    ) {}

    /**
     * Authenticate user and issue token
     *
     * @param  array  $credentials  User credentials (email, password)
     * @param  string  $deviceName  Device name
     * @param  string  $userAgent  User agent string
     * @param  string  $ipAddress  IP address
     * @return array Returns ['token' => string, 'user' => User]
     *
     * @throws InvalidCredentialsException
     */
    public function login(
        array $credentials,
        string $deviceName,
        string $userAgent,
        string $ipAddress
    ): array {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;
        $organizationId = $credentials['organization_id'] ?? null;

        if (! $email || ! $password) {
            $this->logFailedLogin($email, 'missing_credentials');
            throw new InvalidCredentialsException('Email and password are required.');
        }

        // Verify credentials and get user
        $user = $this->verifyCredentials($email, $password, $organizationId);

        // Use transaction to ensure atomicity of device registration and token generation
        return DB::transaction(function () use ($user, $deviceName, $userAgent, $ipAddress) {
            // Register or update device
            $device = $this->registerDevice($user, $deviceName, $userAgent, $ipAddress);

            // Generate JWT token
            $token = $this->jwtTokenService->generate(
                $user->id,
                $device->device_id,
                $user->organization_id,
                $user->tenant_id
            );

            // Mark device as used
            $device->markAsUsed();

            // Log successful login
            $this->auditService->logEvent(
                'login.success',
                User::class,
                $user->id,
                [
                    'device_id' => $device->device_id,
                    'device_name' => $deviceName,
                    'ip_address' => $ipAddress,
                ]
            );

            return [
                'token' => $token,
                'user' => $user->load(['roles', 'permissions', 'organization']),
            ];
        });
    }

    /**
     * Logout user and revoke token
     *
     * @param  string  $token  JWT token to revoke
     *
     * @throws AuthorizationException
     */
    public function logout(string $token): void
    {
        try {
            // Validate and get token claims
            $claims = $this->jwtTokenService->validate($token);

            // Revoke the token
            $revoked = $this->jwtTokenService->revoke($token);

            if (! $revoked) {
                throw new AuthorizationException('Failed to revoke token.');
            }

            // Log logout event
            $this->auditService->logEvent(
                'logout.success',
                User::class,
                $claims['sub'] ?? null,
                [
                    'device_id' => $claims['device_id'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            // Log failed logout attempt
            $this->auditService->logEvent(
                'logout.failed',
                User::class,
                null,
                [
                    'reason' => $e->getMessage(),
                ]
            );

            throw new AuthorizationException('Logout failed: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Refresh JWT token
     *
     * @param  string  $oldToken  Old JWT token
     * @return array Returns ['token' => string]
     *
     * @throws AuthorizationException
     */
    public function refreshToken(string $oldToken): array
    {
        try {
            // Refresh token (validates, revokes old, generates new)
            $newToken = $this->jwtTokenService->refresh($oldToken);

            if (! $newToken) {
                throw new AuthorizationException('Token refresh failed. Token may be expired or invalid.');
            }

            // Get claims from new token for audit
            $claims = $this->jwtTokenService->getClaims($newToken);

            // Log token refresh
            $this->auditService->logEvent(
                'token.refreshed',
                User::class,
                $claims['sub'] ?? null,
                [
                    'device_id' => $claims['device_id'] ?? null,
                ]
            );

            return [
                'token' => $newToken,
            ];
        } catch (\Exception $e) {
            // Log failed refresh
            $this->auditService->logEvent(
                'token.refresh_failed',
                User::class,
                null,
                [
                    'reason' => $e->getMessage(),
                ]
            );

            throw new AuthorizationException('Token refresh failed: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Verify user credentials
     *
     * @param  string  $email  User email
     * @param  string  $password  User password
     * @param  string|null  $organizationId  Optional organization ID
     * @return User Authenticated user
     *
     * @throws InvalidCredentialsException
     */
    public function verifyCredentials(
        string $email,
        string $password,
        ?string $organizationId = null
    ): User {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (! $tenantId) {
            $this->logFailedLogin($email, 'missing_tenant_context');
            throw new InvalidCredentialsException('Tenant context is required for authentication.');
        }

        // Find active user by email
        $user = $this->userRepository->findByEmailForAuth($email, $tenantId);

        if (! $user) {
            $this->logFailedLogin($email, 'user_not_found');
            throw new InvalidCredentialsException('The provided credentials are invalid.');
        }

        // If organization ID is provided, verify user belongs to that organization
        if ($organizationId && $user->organization_id !== $organizationId) {
            $this->logFailedLogin($email, 'organization_mismatch');
            throw new InvalidCredentialsException('The provided credentials are invalid.');
        }

        // Verify password
        if (! Hash::check($password, $user->password)) {
            $this->logFailedLogin($email, 'invalid_password');
            throw new InvalidCredentialsException('The provided credentials are invalid.');
        }

        // Check if user is active
        if (! $user->is_active) {
            $this->logFailedLogin($email, 'user_inactive');
            throw new InvalidCredentialsException('User account is inactive.');
        }

        return $user;
    }

    /**
     * Register or update user device
     *
     * @param  User  $user  User instance
     * @param  string  $deviceName  Device name
     * @param  string  $userAgent  User agent string
     * @param  string  $ipAddress  IP address
     * @return UserDevice Device instance
     */
    public function registerDevice(
        User $user,
        string $deviceName,
        string $userAgent,
        string $ipAddress
    ): UserDevice {
        // Generate or use existing device ID
        $deviceId = $this->generateDeviceId($user->id, $deviceName, $userAgent);

        // Check if device already exists
        $device = $this->userDeviceRepository->findByDeviceId(
            $deviceId,
            $user->id,
            $user->tenant_id
        );

        if ($device) {
            // Update existing device using repository
            $this->userDeviceRepository->update($device->id, [
                'device_name' => $deviceName,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'last_used_at' => now(),
            ]);

            return $this->userDeviceRepository->findOrFail($device->id);
        }

        // Create new device
        $deviceData = [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'device_type' => $this->detectDeviceType($userAgent),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => [],
        ];

        return $this->userDeviceRepository->create($deviceData);
    }

    /**
     * Generate device ID from user ID, device name and user agent
     *
     * @param  string  $userId  User ID
     * @param  string  $deviceName  Device name
     * @param  string  $userAgent  User agent string
     * @return string Device ID
     */
    protected function generateDeviceId(string $userId, string $deviceName, string $userAgent): string
    {
        // Generate consistent device ID based on user, device name and user agent
        // Use delimiter to prevent hash collisions
        return hash('sha256', implode('|', [$userId, $deviceName, $userAgent]));
    }

    /**
     * Detect device type from user agent
     *
     * @param  string  $userAgent  User agent string
     * @return string Device type (mobile, tablet, desktop)
     */
    protected function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        // Check tablet first (Android tablets have 'tablet' keyword, iPads have 'ipad')
        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Log failed login attempt
     *
     * @param  string|null  $email  User email
     * @param  string  $reason  Failure reason
     */
    protected function logFailedLogin(?string $email, string $reason): void
    {
        $this->auditService->logEvent(
            'login.failed',
            User::class,
            null,
            [
                'email' => $email,
                'reason' => $reason,
            ]
        );
    }
}
