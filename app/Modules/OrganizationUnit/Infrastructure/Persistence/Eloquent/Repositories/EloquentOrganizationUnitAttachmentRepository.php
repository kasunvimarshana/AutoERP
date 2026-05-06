<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitAttachmentModel;

class EloquentOrganizationUnitAttachmentRepository extends EloquentRepository implements OrganizationUnitAttachmentRepositoryInterface
{
    public function __construct(OrganizationUnitAttachmentModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (OrganizationUnitAttachmentModel $model): OrganizationUnitAttachment => $this->mapModelToDomainEntity($model));
    }

    public function save(OrganizationUnitAttachment $attachment): OrganizationUnitAttachment
    {
        $data = [
            'tenant_id' => $attachment->getTenantId(),
            'org_unit_id' => $attachment->getOrganizationUnitId(),
            'uuid' => $attachment->getUuid(),
            'name' => $attachment->getName(),
            'file_path' => $attachment->getFilePath(),
            'mime_type' => $attachment->getMimeType(),
            'size' => $attachment->getSize(),
            'type' => $attachment->getType(),
            'metadata' => $attachment->getMetadata(),
        ];

        if ($attachment->getId()) {
            $model = $this->update($attachment->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var OrganizationUnitAttachmentModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByUuid(string $uuid): ?OrganizationUnitAttachment
    {
        /** @var OrganizationUnitAttachmentModel|null $model */
        $model = $this->newScopedQuery()->where('uuid', $uuid)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getByOrganizationUnit(int $organizationUnitId, ?string $type = null): Collection
    {
        $query = $this->newScopedQuery()->where('org_unit_id', $organizationUnitId);
        if ($type !== null) {
            $query->where('type', $type);
        }

        return $this->toDomainCollection($query->get());
    }

    private function mapModelToDomainEntity(OrganizationUnitAttachmentModel $model): OrganizationUnitAttachment
    {
        return new OrganizationUnitAttachment(
            tenantId: (int) $model->tenant_id,
            organizationUnitId: (int) $model->org_unit_id,
            uuid: (string) $model->uuid,
            name: (string) $model->name,
            filePath: (string) $model->file_path,
            mimeType: (string) $model->mime_type,
            size: (int) $model->size,
            type: $model->type,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
