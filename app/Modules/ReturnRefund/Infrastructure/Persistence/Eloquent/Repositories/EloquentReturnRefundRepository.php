<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\ReturnRefund\Domain\Entities\ReturnRefund;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\ReturnRefundRepositoryInterface;
use Modules\ReturnRefund\Domain\ValueObjects\ReturnStatus;
use Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Models\ReturnRefundModel;

class EloquentReturnRefundRepository implements ReturnRefundRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?ReturnRefund
    {
        $model = ReturnRefundModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(int $tenantId, array $filters = []): array
    {
        $query = ReturnRefundModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['rental_id'])) {
            $query->where('rental_id', $filters['rental_id']);
        }

        return $query->orderByDesc('id')->get()->map(fn ($m) => $this->toEntity($m))->all();
    }

    public function findByRental(int $rentalId, int $tenantId): array
    {
        return ReturnRefundModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('rental_id', $rentalId)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function save(ReturnRefund $returnRefund): ReturnRefund
    {
        $data = [
            'tenant_id'        => $returnRefund->tenantId,
            'org_unit_id'      => $returnRefund->orgUnitId,
            'rental_id'        => $returnRefund->rentalId,
            'return_number'    => $returnRefund->returnNumber,
            'status'           => $returnRefund->status->value,
            'returned_at'      => $returnRefund->returnedAt->format('Y-m-d H:i:s'),
            'end_odometer'     => $returnRefund->endOdometer,
            'actual_days'      => $returnRefund->actualDays,
            'rental_charge'    => $returnRefund->rentalCharge,
            'extra_charges'    => $returnRefund->extraCharges,
            'damage_charges'   => $returnRefund->damageCharges,
            'fuel_charges'     => $returnRefund->fuelCharges,
            'deposit_paid'     => $returnRefund->depositPaid,
            'refund_amount'    => $returnRefund->refundAmount,
            'refund_method'    => $returnRefund->refundMethod,
            'inspection_notes' => $returnRefund->inspectionNotes,
            'notes'            => $returnRefund->notes,
            'damage_photos'    => $returnRefund->damagePhotos,
            'metadata'         => $returnRefund->metadata,
            'is_active'        => $returnRefund->isActive,
        ];

        if ($returnRefund->id === null) {
            $data['row_version'] = 1;
            $model = ReturnRefundModel::create($data);
        } else {
            $model = ReturnRefundModel::withoutGlobalScope('tenant')->findOrFail($returnRefund->id);
            $model->update($data);
            $model->increment('row_version');
            $model->refresh();
        }

        return $this->toEntity($model);
    }

    public function updateStatus(int $id, int $tenantId, ReturnStatus $status): void
    {
        ReturnRefundModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['status' => $status->value]);
    }

    public function delete(int $id, int $tenantId): void
    {
        ReturnRefundModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    private function toEntity(ReturnRefundModel $model): ReturnRefund
    {
        return new ReturnRefund(
            id: $model->id,
            tenantId: (int) $model->tenant_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            rentalId: (int) $model->rental_id,
            returnNumber: $model->return_number,
            status: ReturnStatus::from($model->status),
            returnedAt: new DateTimeImmutable($model->returned_at->format('Y-m-d H:i:s')),
            endOdometer: $model->end_odometer !== null ? (string) $model->end_odometer : null,
            actualDays: $model->actual_days !== null ? (string) $model->actual_days : null,
            rentalCharge: (string) $model->rental_charge,
            extraCharges: (string) $model->extra_charges,
            damageCharges: (string) $model->damage_charges,
            fuelCharges: (string) $model->fuel_charges,
            depositPaid: (string) $model->deposit_paid,
            refundAmount: (string) $model->refund_amount,
            refundMethod: $model->refund_method,
            inspectionNotes: $model->inspection_notes,
            notes: $model->notes,
            damagePhotos: $model->damage_photos,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            rowVersion: (int) $model->row_version,
        );
    }
}
