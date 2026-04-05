<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeDefinitionRepositoryInterface;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Models\BarcodeDefinitionModel;

class EloquentBarcodeDefinitionRepository implements BarcodeDefinitionRepositoryInterface
{
    public function __construct(private readonly BarcodeDefinitionModel $model) {}

    private function hydrate(BarcodeDefinitionModel $m): BarcodeDefinition
    {
        return new BarcodeDefinition(
            $m->id,
            $m->tenant_id,
            $m->type,
            $m->value,
            $m->label,
            $m->entity_type,
            $m->entity_id,
            $m->metadata ?? [],
            (bool) $m->is_active,
            $m->created_at,
            $m->updated_at,
        );
    }

    private function persist(BarcodeDefinition $def): BarcodeDefinitionModel
    {
        $data = [
            'tenant_id'   => $def->getTenantId(),
            'type'        => $def->getType(),
            'value'       => $def->getValue(),
            'label'       => $def->getLabel(),
            'entity_type' => $def->getEntityType(),
            'entity_id'   => $def->getEntityId(),
            'metadata'    => $def->getMetadata(),
            'is_active'   => $def->isActive(),
        ];

        if ($def->getId() === null) {
            return $this->model->newQuery()->create($data);
        }

        $m = $this->model->newQuery()->findOrFail($def->getId());
        $m->update($data);

        return $m->fresh();
    }

    public function findById(int $id): ?BarcodeDefinition
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->hydrate($m) : null;
    }

    public function findByValue(int $tenantId, string $value): ?BarcodeDefinition
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('value', $value)
            ->first();

        return $m ? $this->hydrate($m) : null;
    }

    /** @return BarcodeDefinition[] */
    public function findByEntity(int $tenantId, string $entityType, int $entityId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    /** @return BarcodeDefinition[] */
    public function findAll(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    public function save(BarcodeDefinition $barcodeDefinition): BarcodeDefinition
    {
        return $this->hydrate($this->persist($barcodeDefinition));
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }
}
