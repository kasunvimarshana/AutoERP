<?php

declare(strict_types=1);

namespace Modules\Metadata\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Metadata\Domain\Contracts\CustomFieldRepositoryContract;
use Modules\Metadata\Domain\Entities\CustomFieldDefinition;

/**
 * Custom field repository implementation.
 *
 * Tenant-aware via AbstractRepository + HasTenant global scope.
 */
class CustomFieldRepository extends AbstractRepository implements CustomFieldRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = CustomFieldDefinition::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByEntityType(string $entityType): Collection
    {
        return $this->query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
