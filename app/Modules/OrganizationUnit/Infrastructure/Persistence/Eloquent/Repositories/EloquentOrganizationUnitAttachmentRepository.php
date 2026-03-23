<?php

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitAttachmentModel;
use Illuminate\Support\Collection;

class EloquentOrganizationUnitAttachmentRepository extends EloquentRepository implements OrganizationUnitAttachmentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new OrganizationUnitAttachmentModel());
    }

    public function findByUuid(string $uuid): ?OrganizationUnitAttachment
    {
        $model = $this->model->where('uuid', $uuid)->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getByOrganizationUnit(int $orgUnitId, ?string $type = null): Collection
    {
        $query = $this->model->where('organization_unit_id', $orgUnitId);
        if ($type) {
            $query->where('type', $type);
        }
        $models = $query->get();
        return $models->map(fn($m) => $this->toDomainEntity($m));
    }

    public function save(OrganizationUnitAttachment $attachment): OrganizationUnitAttachment
    {
        $data = [
            'tenant_id'              => $attachment->getTenantId(),
            'organization_unit_id'   => $attachment->getOrganizationUnitId(),
            'uuid'                   => $attachment->getUuid(),
            'name'                   => $attachment->getName(),
            'file_path'              => $attachment->getFilePath(),
            'mime_type'              => $attachment->getMimeType(),
            'size'                   => $attachment->getSize(),
            'type'                   => $attachment->getType(),
            'metadata'               => $attachment->getMetadata(),
        ];

        if ($attachment->getId()) {
            $model = $this->update($attachment->getId(), $data);
        } else {
            $model = $this->create($data);
        }
        return $this->toDomainEntity($model);
    }

    private function toDomainEntity(OrganizationUnitAttachmentModel $model): OrganizationUnitAttachment
    {
        return new OrganizationUnitAttachment(
            tenantId: $model->tenant_id,
            organizationUnitId: $model->organization_unit_id,
            uuid: $model->uuid,
            name: $model->name,
            filePath: $model->file_path,
            mimeType: $model->mime_type,
            size: $model->size,
            type: $model->type,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }
}
