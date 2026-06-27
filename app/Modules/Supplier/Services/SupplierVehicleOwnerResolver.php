<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Vehicle\DTOs\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SupplierVehicleOwnerResolver implements VehicleOwnerResolverInterface
{
    public function supports(VehicleOwnerType $type): bool
    {
        return $type === VehicleOwnerType::Supplier;
    }

    public function resolve(
        VehicleOwnerType $type,
        ?int $ownerId,
        int $tenantId,
        ?int $organizationUnitId,
    ): VehicleOwnerSnapshot {
        if (! $this->supports($type) || $ownerId === null || $ownerId < 1) {
            throw new InvalidArgumentException('Supplier vehicle ownership requires a supplier.');
        }

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
            throw new ConflictHttpException('Only an active supplier can be assigned to a vehicle.');
        }

        return new VehicleOwnerSnapshot(
            $type,
            (int) $supplier->getKey(),
            (string) ($supplier->code ?: $supplier->supplier_number),
            (string) $supplier->name,
        );
    }
}
