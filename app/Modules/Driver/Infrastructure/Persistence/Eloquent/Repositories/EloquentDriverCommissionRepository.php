<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Driver\Domain\Entities\DriverCommission;
use Modules\Driver\Domain\RepositoryInterfaces\DriverCommissionRepositoryInterface;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Models\DriverCommissionModel;

class EloquentDriverCommissionRepository implements DriverCommissionRepositoryInterface
{
    public function create(DriverCommission $commission): void
    {
        DriverCommissionModel::create([
            'id' => $commission->id,
            'driver_id' => $commission->driverId,
            'rental_transaction_id' => $commission->rentalTransactionId,
            'commission_amount' => $commission->commissionAmount,
            'commission_percentage' => $commission->commissionPercentage,
            'earned_date' => $commission->earnedDate,
            'paid_date' => $commission->paidDate,
            'status' => $commission->status,
            'tenant_id' => $commission->tenantId,
        ]);
    }

    public function findById(string $id): ?DriverCommission
    {
        $model = DriverCommissionModel::find($id);
        return $model ? $this->modelToEntity($model) : null;
    }

    public function getByDriver(string $driverId, int $page = 1, int $limit = 50): array
    {
        $models = DriverCommissionModel::byDriver($driverId)
            ->paginate($limit, ['*'], 'page', $page);
        
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function getByStatus(string $tenantId, string $status): array
    {
        $query = DriverCommissionModel::byTenant($tenantId);
        
        if ($status === 'pending') {
            $query = $query->pending();
        } elseif ($status === 'paid') {
            $query = $query->paid();
        }

        $models = $query->get();
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function getPendingByDriver(string $driverId): array
    {
        $models = DriverCommissionModel::byDriver($driverId)->pending()->get();
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function update(DriverCommission $commission): void
    {
        DriverCommissionModel::findOrFail($commission->id)->update([
            'commission_amount' => $commission->commissionAmount,
            'commission_percentage' => $commission->commissionPercentage,
            'earned_date' => $commission->earnedDate,
            'paid_date' => $commission->paidDate,
            'status' => $commission->status,
        ]);
    }

    public function delete(string $id): void
    {
        DriverCommissionModel::findOrFail($id)->delete();
    }

    private function modelToEntity(DriverCommissionModel $model): DriverCommission
    {
        return new DriverCommission(
            id: $model->id,
            driverId: $model->driver_id,
            rentalTransactionId: $model->rental_transaction_id,
            commissionAmount: $model->commission_amount,
            commissionPercentage: $model->commission_percentage,
            earnedDate: $model->earned_date,
            paidDate: $model->paid_date,
            status: $model->status,
            tenantId: $model->tenant_id,
        );
    }
}
