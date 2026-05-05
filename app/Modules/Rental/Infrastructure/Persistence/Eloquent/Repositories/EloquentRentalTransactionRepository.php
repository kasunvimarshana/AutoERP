<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalTransaction;
use Modules\Rental\Domain\RepositoryInterfaces\RentalTransactionRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalTransactionModel;

class EloquentRentalTransactionRepository implements RentalTransactionRepositoryInterface
{
    public function create(RentalTransaction $transaction): void
    {
        RentalTransactionModel::create([
            'id' => $transaction->getId(),
            'tenant_id' => $transaction->getTenantId(),
            'agreement_id' => $transaction->getAgreementId(),
            'checked_out_at' => $transaction->getCheckedOutAt(),
            'checked_in_at' => $transaction->getCheckedInAt(),
            'odometer_out' => $transaction->getOdometerOut(),
            'odometer_in' => $transaction->getOdometerIn(),
            'fuel_level_out' => $transaction->getFuelLevelOut(),
            'fuel_level_in' => $transaction->getFuelLevelIn(),
            'pickup_latitude' => $transaction->getPickupLatitude(),
            'pickup_longitude' => $transaction->getPickupLongitude(),
            'dropoff_latitude' => $transaction->getDropoffLatitude(),
            'dropoff_longitude' => $transaction->getDropoffLongitude(),
            'status' => $transaction->getStatus(),
        ]);
    }

    public function findById(string $id): ?RentalTransaction
    {
        $model = RentalTransactionModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findOpenByAgreementId(string $agreementId): ?RentalTransaction
    {
        $model = RentalTransactionModel::where('agreement_id', $agreementId)
            ->where('status', 'open')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function getOpenByTenant(string $tenantId, int $page = 1, int $limit = 50): array
    {
        $query = RentalTransactionModel::byTenant($tenantId)->open();
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn ($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function update(RentalTransaction $transaction): void
    {
        RentalTransactionModel::findOrFail($transaction->getId())->update([
            'checked_in_at' => $transaction->getCheckedInAt(),
            'odometer_in' => $transaction->getOdometerIn(),
            'fuel_level_in' => $transaction->getFuelLevelIn(),
            'dropoff_latitude' => $transaction->getDropoffLatitude(),
            'dropoff_longitude' => $transaction->getDropoffLongitude(),
            'status' => $transaction->getStatus(),
        ]);
    }

    private function toDomain(RentalTransactionModel $model): RentalTransaction
    {
        return new RentalTransaction(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            agreementId: (string) $model->agreement_id,
            checkedOutAt: $model->checked_out_at,
            checkedInAt: $model->checked_in_at,
            odometerOut: (int) $model->odometer_out,
            odometerIn: $model->odometer_in !== null ? (int) $model->odometer_in : null,
            fuelLevelOut: (string) $model->fuel_level_out,
            fuelLevelIn: $model->fuel_level_in,
            pickupLatitude: $model->pickup_latitude !== null ? (string) $model->pickup_latitude : null,
            pickupLongitude: $model->pickup_longitude !== null ? (string) $model->pickup_longitude : null,
            dropoffLatitude: $model->dropoff_latitude !== null ? (string) $model->dropoff_latitude : null,
            dropoffLongitude: $model->dropoff_longitude !== null ? (string) $model->dropoff_longitude : null,
            status: (string) $model->status,
        );
    }
}
