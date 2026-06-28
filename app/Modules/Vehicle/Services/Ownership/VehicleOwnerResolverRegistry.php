<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services\Ownership;

use InvalidArgumentException;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Vehicle\Data\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;

final class VehicleOwnerResolverRegistry
{
    /** @var list<VehicleOwnerResolverInterface> */
    private array $resolvers;

    /** @param iterable<VehicleOwnerResolverInterface> $resolvers */
    public function __construct(iterable $resolvers)
    {
        $this->resolvers = is_array($resolvers) ? array_values($resolvers) : iterator_to_array($resolvers, false);
    }

    public function resolve(VehicleOwnerType $type, int $tenantId, ?int $organizationUnitId, ?int $ownerId): VehicleOwnerSnapshot
    {
        if ($type === VehicleOwnerType::Company) {
            if ($ownerId !== null) {
                throw new InvalidArgumentException('Company ownership must not reference an external party id.');
            }

            return new VehicleOwnerSnapshot($type, null, 'company', 'COMPANY', 'Company Fleet');
        }

        if ($ownerId === null || $ownerId < 1) {
            throw new InvalidArgumentException('A valid owner is required for customer and supplier ownership.');
        }

        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($type)) {
                return $resolver->resolve($tenantId, $organizationUnitId, $ownerId);
            }
        }

        throw new InvalidArgumentException(sprintf('No vehicle owner resolver is registered for [%s].', $type->value));
    }
}
