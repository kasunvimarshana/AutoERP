<?php declare(strict_types=1);

namespace Modules\Driver\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Driver\Application\Contracts\ManageLicenseServiceInterface;
use Modules\Driver\Domain\Entities\License;
use Modules\Driver\Domain\RepositoryInterfaces\LicenseRepositoryInterface;

class ManageLicenseService implements ManageLicenseServiceInterface
{
    public function __construct(
        private readonly LicenseRepositoryInterface $licenses,
    ) {}

    public function create(array $data): License
    {
        return DB::transaction(function () use ($data): License {
            $license = new License(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                driverId: $data['driver_id'],
                licenseNumber: $data['license_number'],
                category: $data['category'] ?? 'C',
                issuedDate: new \DateTime($data['issued_date']),
                expiryDate: new \DateTime($data['expiry_date']),
                issuingCountry: $data['issuing_country'] ?? null,
            );

            $this->licenses->create($license);
            return $license;
        });
    }

    public function update(int $tenantId, string $id, array $data): License
    {
        return DB::transaction(function () use ($tenantId, $id, $data): License {
            $license = $this->licenses->findById($id);
            if (!$license || $license->getTenantId() !== (string) $tenantId) {
                throw new \Exception('License not found');
            }

            $this->licenses->update($license);
            return $this->licenses->findById($id);
        });
    }

    public function find(int $tenantId, string $id): License
    {
        $license = $this->licenses->findById($id);
        if (!$license || $license->getTenantId() !== (string) $tenantId) {
            throw new \Exception('License not found');
        }
        return $license;
    }

    public function delete(int $tenantId, string $id): void
    {
        $this->find($tenantId, $id);
        $this->licenses->delete($id);
    }

    public function getByDriver(int $tenantId, string $driverId): array
    {
        return $this->licenses->getByDriver((string) $tenantId, $driverId);
    }

    public function getExpiring(int $tenantId, int $daysThreshold = 30): array
    {
        return $this->licenses->getExpiring((string) $tenantId, $daysThreshold);
    }
}
