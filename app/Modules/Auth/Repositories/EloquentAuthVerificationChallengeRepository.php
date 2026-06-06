<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\AuthVerificationChallengeModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentAuthVerificationChallengeRepository extends EloquentRepository implements AuthVerificationChallengeRepositoryInterface
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
