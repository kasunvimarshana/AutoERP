<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Driver\Domain\Entities\Driver;
use Modules\Driver\Domain\RepositoryInterfaces\DriverRepositoryInterface;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Models\DriverModel;

class EloquentDriverRepository implements DriverRepositoryInterface
{
    public function create(Driver $driver): void
    {
        DriverModel::create([
            'id' => $driver->id,
            'first_name' => $driver->firstName,
            'last_name' => $driver->lastName,
            'email' => $driver->email,
            'phone' => $driver->phone,
            'date_of_birth' => $driver->dateOfBirth,
            'address' => $driver->address,
            'id_number' => $driver->idNumber,
            'is_available' => $driver->isAvailable,
            'hire_date' => $driver->hireDate,
            'termination_date' => $driver->terminationDate,
            'tenant_id' => $driver->tenantId,
        ]);
    }

    public function findById(string $id): ?Driver
    {
        $model = DriverModel::find($id);
        return $model ? $this->modelToEntity($model) : null;
    }

    public function findByEmail(string $email): ?Driver
    {
        $model = DriverModel::where('email', $email)->first();
        return $model ? $this->modelToEntity($model) : null;
    }

    public function getAllByTenant(string $tenantId, int $page = 1, int $limit = 50): array
    {
        $models = DriverModel::byTenant($tenantId)
            ->paginate($limit, ['*'], 'page', $page);
        
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function getActive(string $tenantId): array
    {
        $models = DriverModel::byTenant($tenantId)->active()->get();
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function getByStatus(string $tenantId, string $status): array
    {
        $query = DriverModel::byTenant($tenantId);
        
        if ($status === 'active') {
            $query = $query->active();
        } elseif ($status === 'terminated') {
            $query = $query->whereNotNull('termination_date');
        }

        $models = $query->get();
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function update(Driver $driver): void
    {
        DriverModel::findOrFail($driver->id)->update([
            'first_name' => $driver->firstName,
            'last_name' => $driver->lastName,
            'email' => $driver->email,
            'phone' => $driver->phone,
            'date_of_birth' => $driver->dateOfBirth,
            'address' => $driver->address,
            'id_number' => $driver->idNumber,
            'is_available' => $driver->isAvailable,
            'hire_date' => $driver->hireDate,
            'termination_date' => $driver->terminationDate,
        ]);
    }

    public function delete(string $id): void
    {
        DriverModel::findOrFail($id)->delete();
    }

    public function countByTenant(string $tenantId): int
    {
        return DriverModel::byTenant($tenantId)->count();
    }

    private function modelToEntity(DriverModel $model): Driver
    {
        return new Driver(
            id: $model->id,
            firstName: $model->first_name,
            lastName: $model->last_name,
            email: $model->email,
            phone: $model->phone,
            dateOfBirth: $model->date_of_birth,
            address: $model->address,
            idNumber: $model->id_number,
            isAvailable: $model->is_available,
            hireDate: $model->hire_date,
            terminationDate: $model->termination_date,
            tenantId: $model->tenant_id,
        );
    }
}
