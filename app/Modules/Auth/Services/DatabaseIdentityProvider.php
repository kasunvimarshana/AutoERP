<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\Providers\IdentityProviderInterface;
use Modules\Auth\Repositories\AuthIdentityRepositoryInterface;
use Modules\Core\DTOs\DataRecord;

final class DatabaseIdentityProvider implements IdentityProviderInterface
{
    public function __construct(private readonly AuthIdentityRepositoryInterface $identities) {}

    public function findByUserAndProvider(?int $tenantId, int $userId, int $providerId): ?DataRecord
    {
        return $this->identities->findByUserAndProvider($tenantId, $userId, $providerId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): DataRecord
    {
        return $this->identities->create($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int|string $id, array $payload): DataRecord
    {
        return $this->identities->update($id, $payload);
    }
}
