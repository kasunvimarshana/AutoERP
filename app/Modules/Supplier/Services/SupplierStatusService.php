<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Supplier\DTOs\SupplierStatusChangeData;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierStatusHistory;

final class SupplierStatusService
{
    public function recordInitial(Supplier $supplier, ?int $changedBy = null): void
    {
        SupplierStatusHistory::query()->create([
            'tenant_id' => $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
            'supplier_id' => $supplier->getKey(),
            'old_status' => null,
            'new_status' => $supplier->status,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    public function change(Supplier $supplier, SupplierStatusChangeData $data): Supplier
    {
        $from = $supplier->status instanceof SupplierStatus
            ? $supplier->status
            : SupplierStatus::from((string) $supplier->status);

        $this->assertTransition($from, $data->newStatus);

        if ($from === $data->newStatus) {
            return $supplier;
        }

        return DB::transaction(function () use ($supplier, $data, $from): Supplier {
            SupplierStatusHistory::query()->create([
                'tenant_id' => $supplier->tenant_id,
                'organization_unit_id' => $supplier->organization_unit_id,
                'supplier_id' => $supplier->getKey(),
                'old_status' => $from,
                'new_status' => $data->newStatus,
                'reason' => $data->reason,
                'changed_by' => $data->changedBy,
                'changed_at' => now(),
            ]);

            $supplier->status = $data->newStatus;
            if ($data->newStatus === SupplierStatus::Active && $supplier->approved_at === null) {
                $supplier->approved_by = $data->changedBy;
                $supplier->approved_at = now();
            }
            $supplier->save();

            return $supplier->refresh();
        });
    }

    public function changeTo(
        Supplier $supplier,
        SupplierStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
    ): Supplier {
        return $this->change($supplier, new SupplierStatusChangeData(
            newStatus: $status,
            reason: $reason,
            changedBy: $changedBy,
        ));
    }

    public function assertTransition(SupplierStatus $from, SupplierStatus $to): void
    {
        $allowed = [
            SupplierStatus::PendingApproval->value => [
                SupplierStatus::Active,
                SupplierStatus::Inactive,
                SupplierStatus::Blacklisted,
            ],
            SupplierStatus::Active->value => [
                SupplierStatus::Inactive,
                SupplierStatus::OnHold,
                SupplierStatus::Blacklisted,
            ],
            SupplierStatus::Inactive->value => [
                SupplierStatus::Active,
                SupplierStatus::PendingApproval,
                SupplierStatus::Blacklisted,
            ],
            SupplierStatus::OnHold->value => [
                SupplierStatus::Active,
                SupplierStatus::Inactive,
                SupplierStatus::Blacklisted,
            ],
            SupplierStatus::Blacklisted->value => [
                SupplierStatus::Inactive,
                SupplierStatus::PendingApproval,
            ],
        ];

        if ($from !== $to && ! in_array($to, $allowed[$from->value] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid supplier status transition.'],
            ]);
        }
    }
}
