<?php

declare(strict_types=1);

namespace Modules\Metadata\Infrastructure\Repositories;

use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Metadata\Domain\Contracts\FeatureFlagRepositoryContract;
use Modules\Metadata\Domain\Entities\FeatureFlag;

/**
 * Feature flag repository implementation.
 *
 * Tenant-aware via AbstractRepository + HasTenant global scope.
 */
class FeatureFlagRepository extends AbstractRepository implements FeatureFlagRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = FeatureFlag::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isFlagEnabled(string $flagKey): bool
    {
        $flag = $this->query()
            ->where('flag_key', $flagKey)
            ->first();

        return $flag !== null && (bool) $flag->flag_value;
    }
}
