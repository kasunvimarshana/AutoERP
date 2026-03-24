<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\Entities\UserAttachment;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserAttachmentModel;

class EloquentUserAttachmentRepository extends EloquentRepository implements UserAttachmentRepositoryInterface
{
    public function __construct(UserAttachmentModel $model)
    {
        parent::__construct($model);
    }

    public function findByUuid(string $uuid): ?UserAttachment
    {
        $model = $this->model->where('uuid', $uuid)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getByUser(int $userId, ?string $type = null): Collection
    {
        $query = $this->model->where('user_id', $userId);
        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->get()->map(fn ($m) => $this->toDomainEntity($m));
    }

    public function save(UserAttachment $attachment): UserAttachment
    {
        $data = [
            'tenant_id' => $attachment->getTenantId(),
            'user_id' => $attachment->getUserId(),
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

    private function toDomainEntity(UserAttachmentModel $model): UserAttachment
    {
        return new UserAttachment(
            tenantId: $model->tenant_id,
            userId: $model->user_id,
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
