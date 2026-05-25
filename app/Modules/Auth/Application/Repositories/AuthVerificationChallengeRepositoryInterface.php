<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AuthVerificationChallengeRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByChallengeKey(?int $tenantId, string $challengeKey): ?DataRecord;
}
