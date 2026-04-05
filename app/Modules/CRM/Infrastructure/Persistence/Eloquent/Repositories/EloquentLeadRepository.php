<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\CRM\Domain\Entities\Lead;
use Modules\CRM\Domain\RepositoryInterfaces\LeadRepositoryInterface;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\LeadModel;

class EloquentLeadRepository implements LeadRepositoryInterface
{
    public function __construct(private readonly LeadModel $model) {}

    public function findById(int $id): ?Lead
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        $q = $this->model->newQuery()->where('tenant_id', $tenantId);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['owner_id'])) $q->where('owner_id', $filters['owner_id']);
        return $q->orderByDesc('created_at')->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): Lead
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?Lead
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(LeadModel $m): Lead
    {
        return new Lead(
            $m->id, $m->tenant_id, $m->name, $m->email, $m->phone, $m->company,
            $m->source, $m->status,
            $m->estimated_value !== null ? (float) $m->estimated_value : null,
            $m->owner_id, $m->notes, $m->converted_at, $m->created_at, $m->updated_at,
        );
    }
}
