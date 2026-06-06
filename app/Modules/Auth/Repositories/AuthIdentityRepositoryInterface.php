<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthIdentityRepositoryInterface extends RepositoryPortInterface
{
    public function findByProviderAndSubject(?int $tenantId, int $providerId, string $providerUserKey): ?DataRecord;

    public function findByUserAndProvider(?int $tenantId, int $userId, int $providerId): ?DataRecord;
}
