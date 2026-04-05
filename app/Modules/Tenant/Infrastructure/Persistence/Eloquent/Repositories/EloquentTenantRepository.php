<?php
declare(strict_types=1);
namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function __construct(private readonly TenantModel $model) {}

    private function toEntity(TenantModel $m): Tenant
    {
        return new Tenant(
            $m->id,
            $m->name,
            $m->slug,
            $m->status,
            $m->plan_type,
            $m->settings,
            $m->trial_ends_at,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?Tenant
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findBySlug(string $slug): ?Tenant
    {
        $m = $this->model->newQuery()->where('slug', $slug)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function create(array $data): Tenant
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?Tenant
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return null;
        }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }
}
