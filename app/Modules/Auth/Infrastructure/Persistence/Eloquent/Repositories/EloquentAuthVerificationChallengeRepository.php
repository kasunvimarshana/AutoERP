<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Application\Repositories\AuthVerificationChallengeRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthVerificationChallengeModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentAuthVerificationChallengeRepository extends EloquentRepository
    implements AuthVerificationChallengeRepositoryInterface
{
    public function __construct(AuthVerificationChallengeModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByChallengeKey(?int $tenantId, string $challengeKey): ?DataRecord
    {
        $query = $this->query()
            ->where('challenge_key', trim($challengeKey))
            ->where('status', 'pending');

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }
}
