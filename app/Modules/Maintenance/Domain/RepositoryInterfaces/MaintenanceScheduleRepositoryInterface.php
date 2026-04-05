<?php
declare(strict_types=1);
namespace Modules\Maintenance\Domain\RepositoryInterfaces;
use Modules\Maintenance\Domain\Entities\MaintenanceSchedule;
interface MaintenanceScheduleRepositoryInterface {
    public function findById(int $id): ?MaintenanceSchedule;
    public function findDue(int $tenantId, \DateTimeInterface $asOf): array;
    public function findAllByTenant(int $tenantId): array;
    public function create(array $data): MaintenanceSchedule;
    public function update(int $id, array $data): ?MaintenanceSchedule;
    public function delete(int $id): bool;
}
