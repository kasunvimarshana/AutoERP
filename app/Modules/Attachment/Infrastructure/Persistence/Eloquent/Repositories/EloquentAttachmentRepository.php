<?php

namespace Modules\Attachment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Attachment\Domain\Entities\Attachment;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;
use Modules\Attachment\Infrastructure\Persistence\Eloquent\Models\AttachmentModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentAttachmentRepository extends EloquentRepository implements AttachmentRepositoryInterface
{
    public function __construct(AttachmentModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Attachment
    {
        /** @var AttachmentModel|null $model */
        $model = $this->model->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByAttachable(string $type, int $id): array
    {
        return $this->model
            ->where('attachable_type', $type)
            ->where('attachable_id', $id)
            ->get()
            ->map(fn(AttachmentModel $m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): Attachment
    {
        /** @var AttachmentModel $model */
        $model = $this->model->create($data);

        return $this->toEntity($model);
    }

    public function delete(Attachment $attachment): bool
    {
        return (bool) $this->model->destroy($attachment->id);
    }

    private function toEntity(AttachmentModel $m): Attachment
    {
        return new Attachment(
            id:             $m->id,
            tenantId:       $m->tenant_id,
            attachableType: $m->attachable_type,
            attachableId:   $m->attachable_id,
            disk:           $m->disk,
            path:           $m->path,
            originalName:   $m->original_name,
            mimeType:       $m->mime_type,
            size:           $m->size,
            label:          $m->label,
            uploadedBy:     $m->uploaded_by,
        );
    }
}
