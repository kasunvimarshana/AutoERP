<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\ReturnRefund\Domain\Entities\ReturnInspection;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\ReturnInspectionRepositoryInterface;
use Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Models\ReturnInspectionModel;

class EloquentReturnInspectionRepository implements ReturnInspectionRepositoryInterface
{
    public function create(ReturnInspection $inspection): void
    {
        ReturnInspectionModel::create([
            'id' => $inspection->getId(),
            'tenant_id' => $inspection->getTenantId(),
            'rental_transaction_id' => $inspection->getRentalTransactionId(),
            'is_damaged' => $inspection->isDamaged(),
            'damage_notes' => $inspection->getDamageNotes(),
            'damage_charge' => $inspection->getDamageCharge(),
            'fuel_adjustment_charge' => $inspection->getFuelAdjustmentCharge(),
            'late_return_charge' => $inspection->getLateReturnCharge(),
            'inspected_at' => $inspection->getInspectedAt(),
        ]);
    }

    public function findById(string $id): ?ReturnInspection
    {
        $model = ReturnInspectionModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByRentalTransactionId(string $rentalTransactionId): ?ReturnInspection
    {
        $model = ReturnInspectionModel::where('rental_transaction_id', $rentalTransactionId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function update(ReturnInspection $inspection): void
    {
        ReturnInspectionModel::findOrFail($inspection->getId())->update([
            'is_damaged' => $inspection->isDamaged(),
            'damage_notes' => $inspection->getDamageNotes(),
            'damage_charge' => $inspection->getDamageCharge(),
            'fuel_adjustment_charge' => $inspection->getFuelAdjustmentCharge(),
            'late_return_charge' => $inspection->getLateReturnCharge(),
            'inspected_at' => $inspection->getInspectedAt(),
        ]);
    }

    private function toDomain(ReturnInspectionModel $model): ReturnInspection
    {
        return new ReturnInspection(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            rentalTransactionId: (string) $model->rental_transaction_id,
            isDamaged: (bool) $model->is_damaged,
            damageNotes: (string) ($model->damage_notes ?? ''),
            damageCharge: (string) $model->damage_charge,
            fuelAdjustmentCharge: (string) $model->fuel_adjustment_charge,
            lateReturnCharge: (string) $model->late_return_charge,
            inspectedAt: $model->inspected_at,
        );
    }
}
