<?php
declare(strict_types=1);
namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Returns\Domain\Entities\ReturnRequest;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnRequestRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnLineModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnRequestModel;

class EloquentReturnRequestRepository implements ReturnRequestRepositoryInterface
{
    public function __construct(
        private readonly ReturnRequestModel $model,
        private readonly ReturnLineModel $lineModel,
    ) {}

    private function toEntity(ReturnRequestModel $m): ReturnRequest
    {
        return new ReturnRequest(
            $m->id,
            $m->tenant_id,
            $m->return_type,
            $m->reference_id,
            $m->return_number,
            $m->status,
            $m->reason,
            $m->notes,
            $m->processed_by,
            $m->lines->toArray(),
            $m->processed_at,
            $m->created_at,
            $m->updated_at,
            $m->return_to        ?? ReturnRequest::RETURN_TO_WAREHOUSE,
            (float)($m->restocking_fee ?? 0.0),
            $m->credit_memo_id   ?? null,
            $m->restocked_by     ?? null,
            $m->restocked_at     ?? null,
        );
    }

    public function findById(int $id): ?ReturnRequest
    {
        $m = $this->model->newQuery()->with('lines')->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $q = $this->model->newQuery()->with('lines')->where('tenant_id', $tenantId);
        if (!empty($filters['return_type'])) $q->where('return_type', $filters['return_type']);
        if (!empty($filters['status']))      $q->where('status', $filters['status']);
        return $q->paginate($perPage, ['*'], 'page', $page)->through(fn($m) => $this->toEntity($m));
    }

    public function create(array $data, array $lines): ReturnRequest
    {
        return DB::transaction(function () use ($data, $lines): ReturnRequest {
            $m = $this->model->newQuery()->create($data);
            foreach ($lines as $l) {
                $this->lineModel->newQuery()->create(array_merge($l, ['return_request_id' => $m->id]));
            }
            return $this->findById($m->id);
        });
    }

    public function update(int $id, array $data): ?ReturnRequest
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->findById($id);
    }

    public function updateLine(int $lineId, array $data): bool
    {
        $line = $this->lineModel->newQuery()->find($lineId);
        if (!$line) return false;
        return (bool)$line->update($data);
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
