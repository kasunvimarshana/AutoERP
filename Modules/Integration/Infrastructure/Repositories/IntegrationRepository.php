<?php

declare(strict_types=1);

namespace Modules\Integration\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use Modules\Integration\Domain\Entities\WebhookDelivery;
use Modules\Integration\Domain\Entities\WebhookEndpoint;

/**
 * Integration repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class IntegrationRepository extends AbstractRepository implements IntegrationRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = WebhookEndpoint::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByEvent(string $eventName): Collection
    {
        return $this->query()
            ->whereJsonContains('events', $eventName)
            ->where('is_active', true)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveEndpoints(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function allDeliveries(): Collection
    {
        return WebhookDelivery::query()->get();
    }
}
