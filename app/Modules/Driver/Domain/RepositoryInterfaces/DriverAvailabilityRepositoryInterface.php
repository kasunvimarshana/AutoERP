<?php declare(strict_types=1);

namespace Modules\Driver\Domain\RepositoryInterfaces;

use Modules\Driver\Domain\Entities\DriverAvailability;

interface DriverAvailabilityRepositoryInterface
{
    public function create(DriverAvailability $availability): void;
    public function findById(string $id): ?DriverAvailability;
    public function getByDriver(string $driverId, int $days = 30): array;
    public function checkAvailability(string $driverId, \DateTime $date, \DateTime $from, \DateTime $until): bool;
    public function update(DriverAvailability $availability): void;
    public function delete(string $id): void;
}
