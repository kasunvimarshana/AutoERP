<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\Providers;

use Modules\Core\DTOs\DataRecord;

interface IdentityProviderInterface
{
    public function findByUserAndProvider(?int $tenantId, int $userId, int $providerId): ?DataRecord;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): DataRecord;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int|string $id, array $payload): DataRecord;
}
