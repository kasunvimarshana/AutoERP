<?php

declare(strict_types=1);

namespace Modules\Driver\Application\Contracts;

use Modules\Driver\Application\DTOs\CreateDriverLicenseDTO;
use Modules\Driver\Domain\Entities\DriverLicense;

interface DriverLicenseServiceInterface
{
    public function getById(int $id): DriverLicense;

    /** @return DriverLicense[] */
    public function listByDriver(int $driverId): array;

    /** @return DriverLicense[] */
    public function listExpiringSoon(int $tenantId, int $daysAhead = 30): array;

    public function create(CreateDriverLicenseDTO $dto): DriverLicense;

    public function update(int $id, CreateDriverLicenseDTO $dto): DriverLicense;

    public function delete(int $id): void;
}
