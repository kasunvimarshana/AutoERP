<?php
declare(strict_types=1);
namespace Modules\Maintenance\Application\Services;

use Modules\Maintenance\Domain\Entities\MaintenanceSchedule;
use Modules\Maintenance\Domain\Exceptions\MaintenanceScheduleNotFoundException;
use Modules\Maintenance\Domain\RepositoryInterfaces\MaintenanceScheduleRepositoryInterface;
use Modules\Maintenance\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;

class MaintenanceScheduleService
{
    public function __construct(
        private readonly MaintenanceScheduleRepositoryInterface $scheduleRepository,
        private readonly ServiceOrderRepositoryInterface $serviceOrderRepository,
    ) {}

    public function findById(int $id): MaintenanceSchedule
    {
        $s = $this->scheduleRepository->findById($id);
        if ($s === null) throw new MaintenanceScheduleNotFoundException($id);
        return $s;
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->scheduleRepository->findAllByTenant($tenantId);
    }

    public function create(array $data): MaintenanceSchedule
    {
        return $this->scheduleRepository->create($data);
    }

    public function update(int $id, array $data): MaintenanceSchedule
    {
        $this->findById($id);
        return $this->scheduleRepository->update($id, $data) ?? $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->scheduleRepository->delete($id);
    }

    /**
     * Generate service orders for all due maintenance schedules and record the run.
     */
    public function processDue(int $tenantId, ?\DateTimeInterface $asOf = null): int
    {
        $asOf ??= new \DateTimeImmutable();
        $due  = $this->scheduleRepository->findDue($tenantId, $asOf);
        $generated = 0;
        foreach ($due as $schedule) {
            $this->serviceOrderRepository->create([
                'tenant_id'      => $schedule->getTenantId(),
                'order_number'   => 'SO-SCH-' . $schedule->getId() . '-' . $asOf->format('YmdHis'),
                'type'           => $schedule->getMaintenanceType(),
                'status'         => 'scheduled',
                'priority'       => 'medium',
                'title'          => $schedule->getName(),
                'asset_id'       => $schedule->getAssetId(),
                'estimated_hours' => 1.0,
                'actual_hours'   => 0.0,
                'labor_cost'     => 0.0,
                'parts_cost'     => 0.0,
                'scheduled_at'   => $asOf,
            ]);
            $schedule->recordRun($asOf);
            $this->scheduleRepository->update($schedule->getId(), [
                'last_run_at' => $asOf,
                'next_run_at' => $schedule->getNextRunAt(),
            ]);
            $generated++;
        }
        return $generated;
    }
}
