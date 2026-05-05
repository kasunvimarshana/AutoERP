<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Domain\Entities\VehicleJobCard;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleJobCardRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleJobCardModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleServicePartUsageModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleServiceTaskModel;

class EloquentVehicleJobCardRepository implements VehicleJobCardRepositoryInterface
{
    public function __construct(
        private readonly VehicleJobCardModel $jobCardModel,
        private readonly VehicleServiceTaskModel $serviceTaskModel,
        private readonly VehicleServicePartUsageModel $partUsageModel,
    ) {}

    public function create(array $data): VehicleJobCard
    {
        return DB::transaction(function () use ($data): VehicleJobCard {
            /** @var VehicleJobCardModel $jobCard */
            $jobCard = $this->jobCardModel->newQuery()->create([
                'tenant_id' => $data['tenant_id'],
                'vehicle_id' => $data['vehicle_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'assigned_mechanic_id' => $data['assigned_mechanic_id'] ?? null,
                'job_card_no' => $data['job_card_no'],
                'workflow_status' => $data['workflow_status'] ?? 'scheduled',
                'service_type' => $data['service_type'] ?? 'maintenance',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'labor_cost_total' => $data['labor_cost_total'] ?? '0.000000',
                'parts_cost_total' => $data['parts_cost_total'] ?? '0.000000',
                'subtotal' => $data['subtotal'] ?? '0.000000',
                'tax_amount' => $data['tax_amount'] ?? '0.000000',
                'grand_total' => $data['grand_total'] ?? '0.000000',
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach (($data['tasks'] ?? []) as $task) {
                $this->serviceTaskModel->newQuery()->create([
                    'tenant_id' => $data['tenant_id'],
                    'job_card_id' => $jobCard->id,
                    'task_name' => $task['task_name'],
                    'task_status' => $task['task_status'] ?? 'pending',
                    'estimated_hours' => $task['estimated_hours'] ?? '0.000000',
                    'actual_hours' => $task['actual_hours'] ?? '0.000000',
                    'labor_rate' => $task['labor_rate'] ?? '0.000000',
                    'labor_cost' => $task['labor_cost'] ?? '0.000000',
                    'notes' => $task['notes'] ?? null,
                ]);
            }

            foreach (($data['parts'] ?? []) as $part) {
                $this->partUsageModel->newQuery()->create([
                    'tenant_id' => $data['tenant_id'],
                    'job_card_id' => $jobCard->id,
                    'service_task_id' => $part['service_task_id'] ?? null,
                    'product_id' => $part['product_id'] ?? null,
                    'uom_id' => $part['uom_id'] ?? null,
                    'quantity' => $part['quantity'] ?? '0.000000',
                    'unit_cost' => $part['unit_cost'] ?? '0.000000',
                    'line_total' => $part['line_total'] ?? '0.000000',
                    'stock_movement_id' => $part['stock_movement_id'] ?? null,
                    'description' => $part['description'] ?? null,
                ]);
            }

            return $this->toEntity($jobCard);
        });
    }

    public function find(int $tenantId, int $jobCardId): ?VehicleJobCard
    {
        /** @var VehicleJobCardModel|null $record */
        $record = $this->jobCardModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $jobCardId)
            ->first();

        return $record !== null ? $this->toEntity($record) : null;
    }

    public function paginate(int $tenantId, int $vehicleId, int $perPage, int $page): mixed
    {
        return $this->jobCardModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function markStatus(int $tenantId, int $jobCardId, string $status): bool
    {
        return $this->jobCardModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $jobCardId)
            ->update([
                'workflow_status' => $status,
                'started_at' => $status === 'in_progress' ? now() : DB::raw('started_at'),
                'completed_at' => $status === 'completed' ? now() : DB::raw('completed_at'),
                'updated_at' => now(),
            ]) > 0;
    }

    private function toEntity(VehicleJobCardModel $model): VehicleJobCard
    {
        return new VehicleJobCard(
            tenantId: (int) $model->tenant_id,
            vehicleId: (int) $model->vehicle_id,
            jobCardNo: (string) $model->job_card_no,
            workflowStatus: (string) $model->workflow_status,
            id: (int) $model->id,
        );
    }
}
