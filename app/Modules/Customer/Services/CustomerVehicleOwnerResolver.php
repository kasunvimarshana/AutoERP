<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Vehicle\Data\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CustomerVehicleOwnerResolver implements VehicleOwnerResolverInterface
{
    public function supports(VehicleOwnerType $type): bool
    {
        return $type === VehicleOwnerType::Customer;
    }

    public function resolve(int $tenantId, ?int $organizationUnitId, int $ownerId): VehicleOwnerSnapshot
    {
        /** @var Customer $customer */
        $customer = Customer::query()
            ->whereKey($ownerId)
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->firstOrFail();

        if ($customer->status !== CustomerStatus::Active) {
            throw new ConflictHttpException('Only an active customer can own or use a vehicle.');
        }

        return new VehicleOwnerSnapshot(
            VehicleOwnerType::Customer,
            (int) $customer->getKey(),
            'customer:'.$customer->getKey(),
            (string) $customer->code,
            (string) ($customer->display_name ?: $customer->name),
        );
    }
}
