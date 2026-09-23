<?php

declare(strict_types=1);

namespace Modules\Vehicle\Contracts;

interface CustomerVehicleProviderInterface
{
    /**
     * @return list<int>
     */
    public function findCurrentCustomerIdsByVehicleNumber(
        int $tenantId,
        ?int $organizationUnitId,
        string $search,
    ): array;

    /**
     * @param  list<int>  $customerIds
     * @return array<int, list<array{
     *     id: int,
     *     registration_number: string|null
     * }>>
     */
    public function getCurrentVehiclesForCustomers(
        int $tenantId,
        ?int $organizationUnitId,
        array $customerIds,
    ): array;
}
