<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\RepositoryInterfaces;

use Modules\Driver\Domain\Entities\DriverLicense;

interface DriverLicenseRepositoryInterface
{
    public function findById(int $id): ?DriverLicense;

    /** @return DriverLicense[] */
    public function findByDriver(int $driverId): array;

    /** @return DriverLicense[] */
    public function findExpiringSoon(int $tenantId, int $daysAhead = 30): array;

    public function save(DriverLicense $license): DriverLicense;

    public function delete(int $id): void;
}
