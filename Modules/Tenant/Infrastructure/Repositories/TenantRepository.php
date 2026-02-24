<?php
namespace Modules\Tenant\Infrastructure\Repositories;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Models\TenantModel;
class TenantRepository implements TenantRepositoryInterface
{
    public function __construct(private TenantModel $model) {}
    public function findById(string $id): ?object { return $this->model->find($id); }
    public function findBySlug(string $slug): ?object { return $this->model->where('slug', $slug)->first(); }
    public function findByDomain(string $domain): ?object { return $this->model->where('domain', $domain)->first(); }
    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = $this->model->newQuery();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('slug', 'like', '%' . $filters['search'] . '%');
            });
        }
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
    public function create(array $data): object { return $this->model->create($data); }
    public function update(string $id, array $data): object
    {
        $tenant = $this->model->findOrFail($id);
        $tenant->update($data);
        return $tenant->fresh();
    }
    public function suspend(string $id): bool
    {
        return (bool) $this->model->where('id', $id)->update(['status' => 'suspended']);
    }
    public function activate(string $id): bool
    {
        return (bool) $this->model->where('id', $id)->update(['status' => 'active']);
    }
}
