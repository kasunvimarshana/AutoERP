<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Models\OutboxEvent;
use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DeviceRepositoryInterface $deviceRepository,
        private readonly TokenService $tokenService,
        private readonly AuditService $auditService,
        private readonly RevocationService $revocationService,
    ) {}

    public function login(
        string $email,
        string $password,
        ?string $tenantId,
        array $deviceInfo,
        Request $request
    ): array {
        $user = $this->userRepository->findByEmail($email, $tenantId);

        if ($user === null || !Hash::check($password, $user->password)) {
            $this->auditService->log(
                eventType: 'auth',
                action: 'login_failed',
                context: [
                    'tenant_id' => $tenantId,
                    'status'    => 'failure',
                    'metadata'  => ['email' => $email],
                ],
                request: $request
            );
            throw AuthException::invalidCredentials();
        }

        if (!$user->isActive()) {
            throw AuthException::accountInactive();
        }

        return DB::transaction(function () use ($user, $deviceInfo, $request, $tenantId): array {
            $deviceId = $deviceInfo['device_id'] ?? (string) Str::uuid();

            $this->deviceRepository->createOrUpdate($user->id, $deviceId, [
                'tenant_id'      => $tenantId,
                'device_name'    => $deviceInfo['device_name'] ?? null,
                'device_type'    => $deviceInfo['device_type'] ?? null,
                'platform'       => $deviceInfo['platform'] ?? null,
                'user_agent'     => $request->userAgent(),
                'ip_address'     => $request->ip(),
                'last_active_at' => now(),
            ]);

            $tokenData = $this->tokenService->issueTokenPair($user, $deviceId);

            OutboxEvent::create([
                'id'             => (string) Str::uuid(),
                'tenant_id'      => $tenantId,
                'aggregate_type' => 'user',
                'aggregate_id'   => (string) $user->id,
                'event_type'     => 'user.logged_in',
                'payload'        => [
                    'user_id'   => $user->id,
                    'device_id' => $deviceId,
                    'ip'        => $request->ip(),
                ],
            ]);

            $this->userRepository->updateLastLogin($user->id, $request->ip() ?? '');
            $this->auditService->logAuthEvent('login', $user->id, $tenantId, $request);

            return $tokenData;
        });
    }

    public function logout(User $user, ?string $jti, Request $request): void
    {
        DB::transaction(function () use ($user, $jti, $request): void {
            if ($jti !== null) {
                $user->tokens()->where('id', $jti)->update(['revoked' => true]);
                $this->revocationService->revokeToken($jti);
            } else {
                $this->tokenService->revokeAllUserTokens($user);
            }

            $this->auditService->logAuthEvent('logout', $user->id, $user->tenant_id, $request);
        });
    }

    public function logoutAllDevices(User $user, Request $request): void
    {
        DB::transaction(function () use ($user, $request): void {
            $this->tokenService->revokeAllUserTokens($user);
            $this->userRepository->incrementTokenVersion($user->id);
            $this->auditService->logAuthEvent('logout_all_devices', $user->id, $user->tenant_id, $request);
        });
    }

    public function register(array $data, ?string $tenantId, Request $request): array
    {
        return DB::transaction(function () use ($data, $tenantId, $request): array {
            $user = $this->userRepository->create([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'password'      => $data['password'],
                'tenant_id'     => $tenantId,
                'status'        => 'active',
                'token_version' => 1,
            ]);

            OutboxEvent::create([
                'id'             => (string) Str::uuid(),
                'tenant_id'      => $tenantId,
                'aggregate_type' => 'user',
                'aggregate_id'   => (string) $user->id,
                'event_type'     => 'user.registered',
                'payload'        => [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                ],
            ]);

            $this->auditService->logAuthEvent('register', $user->id, $tenantId, $request);

            $tokenData = $this->tokenService->issueTokenPair($user);

            return ['user' => $user, 'token' => $tokenData];
        });
    }

    public function refreshToken(User $user, string $oldJti, Request $request): array
    {
        $user->tokens()->where('id', $oldJti)->update(['revoked' => true]);
        $this->revocationService->revokeToken($oldJti);

        $tokenData = $this->tokenService->issueTokenPair($user);

        $this->auditService->logAuthEvent('token_refresh', $user->id, $user->tenant_id, $request);

        return $tokenData;
    }
}
