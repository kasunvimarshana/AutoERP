<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalCharge;
use Modules\Rental\Domain\RepositoryInterfaces\RentalChargeRepositoryInterface;
use Modules\Rental\Domain\ValueObjects\ChargeType;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalChargeModel;

class EloquentRentalChargeRepository implements RentalChargeRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?RentalCharge
    {
        $model = RentalChargeModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByRental(int $rentalId, int $tenantId): array
    {
        return RentalChargeModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('rental_id', $rentalId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function save(RentalCharge $charge): RentalCharge
    {
        $data = [
            'tenant_id'   => $charge->tenantId,
            'rental_id'   => $charge->rentalId,
            'charge_type' => $charge->chargeType->value,
            'description' => $charge->description,
            'quantity'    => $charge->quantity,
            'unit_price'  => $charge->unitPrice,
            'amount'      => $charge->amount,
            'is_active'   => $charge->isActive,
        ];

        if ($charge->id === null) {
            $model = RentalChargeModel::create($data);
        } else {
            $model = RentalChargeModel::withoutGlobalScope('tenant')->findOrFail($charge->id);
            $model->update($data);
            $model->refresh();
        }

        return $this->toEntity($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        RentalChargeModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    private function toEntity(RentalChargeModel $model): RentalCharge
    {
        return new RentalCharge(
            id: $model->id,
            tenantId: (int) $model->tenant_id,
            rentalId: (int) $model->rental_id,
            chargeType: ChargeType::from($model->charge_type),
            description: $model->description,
            quantity: (string) $model->quantity,
            unitPrice: (string) $model->unit_price,
            amount: (string) $model->amount,
            isActive: (bool) $model->is_active,
        );
    }
}
