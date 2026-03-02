<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Pricing repository contract.
 */
interface PricingRepositoryContract extends RepositoryContract
{
    /**
     * Return all discount rules (tenant-scoped).
     */
    public function allDiscountRules(): Collection;

    /**
     * Create a new discount rule.
     *
     * @param array<string, mixed> $data
     */
    public function createDiscountRule(array $data): Model;

    /**
     * Find a discount rule or throw ModelNotFoundException.
     */
    public function findDiscountRuleOrFail(int|string $id): Model;

    /**
     * Update a discount rule.
     *
     * @param array<string, mixed> $data
     */
    public function updateDiscountRule(int|string $id, array $data): Model;

    /**
     * Delete a discount rule.
     */
    public function deleteDiscountRule(int|string $id): bool;
}
