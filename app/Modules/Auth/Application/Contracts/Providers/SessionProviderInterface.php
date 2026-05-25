<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\Providers;

interface SessionProviderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array;

    public function revoke(int|string $sessionId, ?int $tenantId = null): bool;

    public function tenantIdForSession(int|string $sessionId): ?int;

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId, ?int $tenantId = null): array;
}
