<?php
namespace Modules\Sales\Infrastructure\Repositories;
use Modules\Sales\Domain\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Infrastructure\Models\CustomerModel;
class CustomerRepository implements CustomerRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return CustomerModel::find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = CustomerModel::query();
        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                  ->orWhere('email', 'like', '%'.$filters['search'].'%');
            });
        }
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        return CustomerModel::create($data);
    }
    public function update(string $id, array $data): object
    {
        $customer = CustomerModel::findOrFail($id);
        $customer->update($data);
        return $customer->fresh();
    }
    public function delete(string $id): bool
    {
        return CustomerModel::findOrFail($id)->delete();
    }
}
