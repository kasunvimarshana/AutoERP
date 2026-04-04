<?php
namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class EloquentTenantRepository extends EloquentRepository implements TenantRepositoryInterface
{
    public function __construct(TenantModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Tenant
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findBySlug(string $slug): ?Tenant
    {
        $model = $this->model->where('slug', $slug)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return parent::findAll($filters, $perPage);
    }

    public function create(array $data): Tenant
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $model = $this->model->findOrFail($tenant->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(Tenant $tenant): bool
    {
        $model = $this->model->findOrFail($tenant->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): Tenant
    {
        return new Tenant(
            id: $model->id,
            name: $model->name,
            slug: $model->slug,
            email: $model->email,
            status: $model->status,
            plan: $model->plan,
        );
    }
}
