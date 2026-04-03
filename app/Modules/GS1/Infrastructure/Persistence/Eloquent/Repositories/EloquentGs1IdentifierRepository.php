<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\GS1\Domain\Entities\Gs1Identifier;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1IdentifierRepositoryInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1IdentifierModel;

class EloquentGs1IdentifierRepository extends EloquentRepository implements Gs1IdentifierRepositoryInterface
{
    public function __construct(Gs1IdentifierModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (Gs1IdentifierModel $m): Gs1Identifier => $this->mapModelToDomainEntity($m));
    }

    public function save(Gs1Identifier $identifier): Gs1Identifier
    {
        $savedModel = null;
        DB::transaction(function () use ($identifier, &$savedModel) {
            $data = [
                'tenant_id'        => $identifier->getTenantId(),
                'identifier_type'  => $identifier->getIdentifierType(),
                'identifier_value' => $identifier->getIdentifierValue(),
                'entity_type'      => $identifier->getEntityType(),
                'entity_id'        => $identifier->getEntityId(),
                'is_active'        => $identifier->isActive(),
                'metadata'         => $identifier->getMetadata()->toArray(),
            ];
            if ($identifier->getId()) {
                $savedModel = $this->update($identifier->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof Gs1IdentifierModel) {
            throw new \RuntimeException('Failed to save Gs1Identifier.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByValue(int $tenantId, string $type, string $value): ?Gs1Identifier
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('identifier_type', $type)
            ->where('identifier_value', $value)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByEntity(int $tenantId, string $entityType, int $entityId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(Gs1IdentifierModel $model): Gs1Identifier
    {
        return new Gs1Identifier(
            tenantId:        $model->tenant_id,
            identifierType:  $model->identifier_type,
            identifierValue: $model->identifier_value,
            entityType:      $model->entity_type,
            entityId:        $model->entity_id,
            isActive:        (bool) $model->is_active,
            metadata:        isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at,
        );
    }
}
