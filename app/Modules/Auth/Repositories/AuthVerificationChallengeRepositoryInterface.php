<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface AuthVerificationChallengeRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByChallengeKey(?int $tenantId, string $challengeKey): ?DataRecord;
}
