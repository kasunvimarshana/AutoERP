<?php

declare(strict_types=1);

namespace Modules\Driver\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Driver\Application\Contracts\DriverLicenseServiceInterface;
use Modules\Driver\Application\DTOs\CreateDriverLicenseDTO;
use Modules\Driver\Domain\Entities\DriverLicense;
use Modules\Driver\Domain\Exceptions\DriverNotFoundException;
use Modules\Driver\Domain\RepositoryInterfaces\DriverLicenseRepositoryInterface;

class DriverLicenseService implements DriverLicenseServiceInterface
{
    public function __construct(
        private readonly DriverLicenseRepositoryInterface $licenses,
    ) {}

    public function getById(int $id): DriverLicense
    {
        return $this->licenses->findById($id)
            ?? throw new DriverNotFoundException($id);
    }

    public function listByDriver(int $driverId): array
    {
        return $this->licenses->findByDriver($driverId);
    }

    public function listExpiringSoon(int $tenantId, int $daysAhead = 30): array
    {
        return $this->licenses->findExpiringSoon($tenantId, $daysAhead);
    }

    public function create(CreateDriverLicenseDTO $dto): DriverLicense
    {
        return DB::transaction(function () use ($dto): DriverLicense {
            $license = new DriverLicense(
                id: null,
                tenantId: $dto->tenantId,
                driverId: $dto->driverId,
                licenseNumber: $dto->licenseNumber,
                licenseClass: $dto->licenseClass,
                issuedCountry: $dto->issuedCountry,
                issueDate: $dto->issueDate,
                expiryDate: $dto->expiryDate,
                filePath: $dto->filePath,
                isActive: true,
            );

            return $this->licenses->save($license);
        });
    }

    public function update(int $id, CreateDriverLicenseDTO $dto): DriverLicense
    {
        return DB::transaction(function () use ($id, $dto): DriverLicense {
            $license = $this->getById($id);

            $license->licenseNumber = $dto->licenseNumber;
            $license->licenseClass  = $dto->licenseClass;
            $license->issuedCountry = $dto->issuedCountry;
            $license->issueDate     = $dto->issueDate;
            $license->expiryDate    = $dto->expiryDate;
            $license->filePath      = $dto->filePath;

            return $this->licenses->save($license);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->getById($id);
            $this->licenses->delete($id);
        });
    }
}
