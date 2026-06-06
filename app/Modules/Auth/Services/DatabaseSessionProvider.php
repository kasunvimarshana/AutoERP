<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use Modules\Auth\Contracts\Providers\SessionProviderInterface;
use Modules\Auth\Repositories\AuthSessionRepositoryInterface;

final class DatabaseSessionProvider implements SessionProviderInterface
{
    public function __construct(private readonly AuthSessionRepositoryInterface $sessions) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        $session = $this->sessions->create([
            'tenant_id' => $payload['tenant_id'] ?? null,
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'provider_id' => $payload['provider_id'] ?? null,
            'identity_id' => $payload['identity_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'session_key' => Str::random(48),
            'status' => 'active',
            'ip_address' => $payload['ip_address'] ?? null,
            'user_agent' => $payload['user_agent'] ?? null,
            'device_name' => $payload['device_name'] ?? null,
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(30),
            'row_version' => 1,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        return $session->toArray();
    }

    public function revoke(int|string $sessionId, ?int $tenantId = null): bool
    {
        $session = $this->sessions->findById($sessionId);
        if ($session === null) {
            return false;
        }

        $sessionTenantId = $session->get('tenant_id');
        if ($tenantId !== null && (int) ($sessionTenantId ?? 0) !== $tenantId) {
            return false;
        }

        $this->sessions->update($sessionId, [
            'status' => 'revoked',
            'revoked_at' => now(),
            'row_version' => ((int) $session->get('row_version', 1)) + 1,
        ]);

        return true;
    }

    public function tenantIdForSession(int|string $sessionId): ?int
    {
        $session = $this->sessions->findById($sessionId);
        if ($session === null) {
            return null;
        }

        $tenantId = $session->get('tenant_id');

        return $tenantId !== null ? (int) $tenantId : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId, ?int $tenantId = null): array
    {
        $records = $this->sessions->listActiveByUser($tenantId, $userId);

        return array_map(static fn ($record) => $record->toArray(), $records);
    }
}
