<?php

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnAuthorizationModel;

class EloquentReturnAuthorizationRepository extends EloquentRepository implements ReturnAuthorizationRepositoryInterface
{
    public function __construct(ReturnAuthorizationModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?ReturnAuthorization
    {
        $model = parent::findById($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByRmaNumber(int $tenantId, string $rmaNumber): ?ReturnAuthorization
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('rma_number', $rmaNumber)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function create(array $data): ReturnAuthorization
    {
        $model = parent::create($data);

        return $this->toEntity($model);
    }

    public function update(ReturnAuthorization $rma, array $data): ReturnAuthorization
    {
        $model = $this->model->findOrFail($rma->id);
        $updated = parent::update($model, $data);

        return $this->toEntity($updated);
    }

    public function save(ReturnAuthorization $rma): ReturnAuthorization
    {
        $model = $this->model->findOrFail($rma->id);
        $updated = parent::update($model, [
            'status'      => $rma->status,
            'approved_by' => $rma->approvedBy,
            'approved_at' => $rma->approvedAt,
            'expires_at'  => $rma->expiresAt,
            'notes'       => $rma->notes,
        ]);

        return $this->toEntity($updated);
    }

    private function toEntity(object $model): ReturnAuthorization
    {
        return new ReturnAuthorization(
            id: $model->id,
            tenantId: $model->tenant_id,
            rmaNumber: $model->rma_number,
            stockReturnId: $model->stock_return_id,
            status: $model->status,
            expiresAt: $model->expires_at ? new \DateTimeImmutable((string) $model->expires_at) : null,
            approvedBy: $model->approved_by,
            approvedAt: $model->approved_at ? new \DateTimeImmutable((string) $model->approved_at) : null,
            notes: $model->notes,
        );
    }
}
