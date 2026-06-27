<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Vehicle\DTOs\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CustomerVehicleOwnerResolver implements VehicleOwnerResolverInterface
{
    public function supports(VehicleOwnerType $type): bool
    {
        return $type === VehicleOwnerType::Customer;
    }

    public function resolve(
        VehicleOwnerType $type,
        ?int $ownerId,
        int $tenantId,
        ?int $organizationUnitId,
    ): VehicleOwnerSnapshot {
        if (! $this->supports($type) || $ownerId === null || $ownerId < 1) {
            throw new InvalidArgumentException('Customer vehicle ownership requires a customer.');
        }

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
            throw new ConflictHttpException('Only an active customer can be assigned to a vehicle.');
        }

        return new VehicleOwnerSnapshot(
            $type,
            (int) $customer->getKey(),
            (string) ($customer->code ?: $customer->customer_number),
            (string) ($customer->display_name ?: $customer->name),
        );
    }
}
