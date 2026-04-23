<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\EmployeeDocument;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeDocumentRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeDocumentModel;

class EloquentEmployeeDocumentRepository extends EloquentRepository implements EmployeeDocumentRepositoryInterface
{
    public function __construct(EmployeeDocumentModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (EmployeeDocumentModel $m): EmployeeDocument => $this->map($m));
    }

    public function save(EmployeeDocument $e): EmployeeDocument
    {
        $data = ['tenant_id' => $e->getTenantId(), 'employee_id' => $e->getEmployeeId(), 'document_type' => $e->getDocumentType(), 'title' => $e->getTitle(), 'description' => $e->getDescription(), 'file_path' => $e->getFilePath(), 'mime_type' => $e->getMimeType(), 'file_size' => $e->getFileSize(), 'issued_date' => $e->getIssuedDate()?->format('Y-m-d'), 'expiry_date' => $e->getExpiryDate()?->format('Y-m-d'), 'metadata' => $e->getMetadata()];
        $m = $e->getId() ? $this->update($e->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($m);
    }

    public function find(int|string $id, array $columns = ['*']): ?EmployeeDocument
    {
        return parent::find($id, $columns);
    }

    public function findByEmployee(int $tenantId, int $employeeId): array
    {
        return $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->get()->map(fn ($m) => $this->toDomainEntity($m))->toArray();
    }

    private function map(EmployeeDocumentModel $m): EmployeeDocument
    {
        $now = fn ($v) => $v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v ?? 'now');
        $dt = fn ($v) => $v ? ($v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v)) : null;

        return new EmployeeDocument($m->tenant_id, $m->employee_id, $m->document_type, $m->title, $m->description ?? '', $m->file_path, $m->mime_type, (int) $m->file_size, $dt($m->issued_date), $dt($m->expiry_date), $m->metadata ?? [], $now($m->created_at), $now($m->updated_at), $m->id);
    }
}
