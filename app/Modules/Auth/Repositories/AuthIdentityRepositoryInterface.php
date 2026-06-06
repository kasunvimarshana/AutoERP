<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface AuthIdentityRepositoryInterface extends RepositoryPortInterface
{
    public function findByProviderAndSubject(?int $tenantId, int $providerId, string $providerUserKey): ?DataRecord;

    public function findByUserAndProvider(?int $tenantId, int $userId, int $providerId): ?DataRecord;
}
