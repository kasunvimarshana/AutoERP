<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthVerificationChallengeRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByChallengeKey(?int $tenantId, string $challengeKey): ?DataRecord;
}
