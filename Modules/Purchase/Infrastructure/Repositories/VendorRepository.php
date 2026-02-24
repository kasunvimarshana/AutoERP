<?php
namespace Modules\Purchase\Infrastructure\Repositories;
use Modules\Purchase\Domain\Contracts\VendorRepositoryInterface;
use Modules\Purchase\Infrastructure\Models\VendorModel;
class VendorRepository implements VendorRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return VendorModel::find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = VendorModel::query();
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
        return VendorModel::create($data);
    }
    public function update(string $id, array $data): object
    {
        $vendor = VendorModel::findOrFail($id);
        $vendor->update($data);
        return $vendor->fresh();
    }
    public function delete(string $id): bool
    {
        return VendorModel::findOrFail($id)->delete();
    }
}
