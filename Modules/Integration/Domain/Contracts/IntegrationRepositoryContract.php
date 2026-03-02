<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Integration repository contract.
 *
 * Extends the base repository contract with integration-specific query methods.
 */
interface IntegrationRepositoryContract extends RepositoryContract
{
    /**
     * Find active webhook endpoints subscribed to a given event name (tenant-scoped).
     */
    public function findByEvent(string $eventName): Collection;

    /**
     * Find all active webhook endpoints (tenant-scoped).
     */
    public function findActiveEndpoints(): Collection;

    /**
     * Return all webhook delivery records.
     */
    public function allDeliveries(): \Illuminate\Database\Eloquent\Collection;
}
