<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\CRM\Domain\Entities\Opportunity;
use Modules\CRM\Domain\RepositoryInterfaces\OpportunityRepositoryInterface;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\OpportunityModel;

class EloquentOpportunityRepository implements OpportunityRepositoryInterface
{
    public function __construct(private readonly OpportunityModel $model) {}

    public function findById(int $id): ?Opportunity
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        $q = $this->model->newQuery()->where('tenant_id', $tenantId);
        if (!empty($filters['stage'])) $q->where('stage', $filters['stage']);
        if (!empty($filters['owner_id'])) $q->where('owner_id', $filters['owner_id']);
        return $q->orderByDesc('created_at')->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function findByStage(int $tenantId, string $stage): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)->where('stage', $stage)
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): Opportunity
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?Opportunity
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

    private function toEntity(OpportunityModel $m): Opportunity
    {
        return new Opportunity(
            $m->id, $m->tenant_id, $m->name,
            $m->contact_id, $m->customer_id, $m->owner_id,
            $m->stage, (float) $m->probability, (float) $m->amount, $m->currency,
            $m->expected_close_date, $m->description, $m->closed_at,
            $m->created_at, $m->updated_at,
        );
    }
}
