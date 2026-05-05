<?php declare(strict_types=1);

namespace Modules\Driver\Domain\RepositoryInterfaces;

use Modules\Driver\Domain\Entities\License;

interface LicenseRepositoryInterface
{
    public function create(License $license): void;
    public function findById(string $id): ?License;
    public function findByNumber(string $licenseNumber): ?License;
    public function getByDriver(string $driverId): array;
    public function getExpiringLicenses(string $tenantId, int $daysThreshold = 30): array;
    public function getExpiredLicenses(string $tenantId): array;
    public function update(License $license): void;
    public function delete(string $id): void;
}
