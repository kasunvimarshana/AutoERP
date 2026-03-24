<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tenant\Domain\Entities\TenantAttachment;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantAttachmentModel;

class EloquentTenantAttachmentRepository extends EloquentRepository implements TenantAttachmentRepositoryInterface
{
    public function __construct(TenantAttachmentModel $model)
    {
        parent::__construct($model);
    }

    public function findByUuid(string $uuid): ?TenantAttachment
    {
        $model = $this->model->where('uuid', $uuid)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getByTenant(int $tenantId, ?string $type = null): Collection
    {
        $query = $this->model->where('tenant_id', $tenantId);
        if ($type) {
            $query->where('type', $type);
        }
        $models = $query->get();

        return $models->map(fn ($m) => $this->toDomainEntity($m));
    }

    public function save(TenantAttachment $attachment): TenantAttachment
    {
        $data = [
            'tenant_id' => $attachment->getTenantId(),
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

        return $this->toDomainEntity($model);
    }

    private function toDomainEntity(TenantAttachmentModel $model): TenantAttachment
    {
        return new TenantAttachment(
            tenantId: $model->tenant_id,
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
