<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use InvalidArgumentException;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Vehicle\DTOs\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;

final class VehicleOwnerDirectory
{
    /** @param iterable<VehicleOwnerResolverInterface> $resolvers */
    public function __construct(private readonly iterable $resolvers) {}

    public function resolve(
        VehicleOwnerType $type,
        ?int $ownerId,
        int $tenantId,
        ?int $organizationUnitId,
    ): VehicleOwnerSnapshot {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($type)) {
                return $resolver->resolve($type, $ownerId, $tenantId, $organizationUnitId);
            }
        }

        throw new InvalidArgumentException('Vehicle owner type is not registered.');
    }
}
