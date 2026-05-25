<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AuthIdentityRepositoryInterface extends RepositoryPortInterface
{
    public function findByProviderAndSubject(?int $tenantId, int $providerId, string $providerUserKey): ?DataRecord;

    public function findByUserAndProvider(?int $tenantId, int $userId, int $providerId): ?DataRecord;
}
