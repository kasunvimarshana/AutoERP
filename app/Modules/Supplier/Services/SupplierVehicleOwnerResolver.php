<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Vehicle\Data\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SupplierVehicleOwnerResolver implements VehicleOwnerResolverInterface
{
    public function supports(VehicleOwnerType $type): bool
    {
        return $type === VehicleOwnerType::Supplier;
    }

    public function resolve(int $tenantId, ?int $organizationUnitId, int $ownerId): VehicleOwnerSnapshot
    {
        /** @var Supplier $supplier */
        $supplier = Supplier::query()
            ->whereKey($ownerId)
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->firstOrFail();

        if ($supplier->status !== SupplierStatus::Active) {
            throw new ConflictHttpException('Only an active supplier can own or provide a vehicle.');
        }

        return new VehicleOwnerSnapshot(
            VehicleOwnerType::Supplier,
            (int) $supplier->getKey(),
            'supplier:'.$supplier->getKey(),
            (string) $supplier->code,
            (string) ($supplier->display_name ?: $supplier->name),
        );
    }
}
