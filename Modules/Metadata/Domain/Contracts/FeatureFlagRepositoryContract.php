<?php

declare(strict_types=1);

namespace Modules\Metadata\Domain\Contracts;

use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Feature flag repository contract.
 */
interface FeatureFlagRepositoryContract extends RepositoryContract
{
    /**
     * Return whether a given feature flag is enabled for the current tenant.
     */
    public function isFlagEnabled(string $flagKey): bool;
}
