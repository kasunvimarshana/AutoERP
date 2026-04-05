<?php
declare(strict_types=1);
namespace Modules\Attachment\Infrastructure\Persistence\Eloquent\Repositories;
use Modules\Attachment\Domain\Entities\Attachment;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;
use Modules\Attachment\Infrastructure\Persistence\Eloquent\Models\AttachmentModel;
class EloquentAttachmentRepository implements AttachmentRepositoryInterface {
    public function __construct(private readonly AttachmentModel $model) {}
    private function toEntity(AttachmentModel $m): Attachment {
        return new Attachment($m->id,$m->tenant_id,$m->attachable_type,$m->attachable_id,$m->filename,
            $m->original_name,$m->mime_type,(int)$m->size,$m->path,$m->disk,$m->category,$m->uploaded_by,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?Attachment { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByAttachable(string $type, int $id): array {
        return $this->model->newQuery()->where('attachable_type',$type)->where('attachable_id',$id)->get()->map(fn($m)=>$this->toEntity($m))->all();
    }
    public function create(array $data): Attachment { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
