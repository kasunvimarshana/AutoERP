<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ServiceCenter\Application\Contracts\ServiceJobServiceInterface;
use Modules\ServiceCenter\Application\DTOs\CreateServiceJobDTO;
use Modules\ServiceCenter\Application\DTOs\UpdateServiceJobDTO;
use Modules\ServiceCenter\Domain\Exceptions\ServiceJobNotFoundException;
use Modules\ServiceCenter\Domain\ValueObjects\JobType;
use Modules\ServiceCenter\Domain\ValueObjects\ServiceJobStatus;
use Modules\ServiceCenter\Infrastructure\Http\Requests\ChangeServiceJobStatusRequest;
use Modules\ServiceCenter\Infrastructure\Http\Requests\CreateServiceJobRequest;
use Modules\ServiceCenter\Infrastructure\Http\Requests\UpdateServiceJobRequest;

class ServiceJobController extends Controller
{
    public function __construct(
        private readonly ServiceJobServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $filters = $request->only(['status', 'vehicle_id', 'job_type']);

        return response()->json($this->service->listByTenant($tenantId, $filters));
    }

    public function byVehicle(Request $request, int $vehicleId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');

        return response()->json($this->service->listByVehicle($vehicleId, $tenantId));
    }

    public function store(CreateServiceJobRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $data = $request->validated();

        $dto = new CreateServiceJobDTO(
            tenantId: $tenantId,
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            vehicleId: (int) $data['vehicle_id'],
            driverId: isset($data['driver_id']) ? (int) $data['driver_id'] : null,
            jobNumber: $data['job_number'],
            jobType: JobType::from($data['job_type']),
            scheduledAt: new DateTimeImmutable($data['scheduled_at']),
            startedAt: isset($data['started_at']) ? new DateTimeImmutable($data['started_at']) : null,
            completedAt: isset($data['completed_at']) ? new DateTimeImmutable($data['completed_at']) : null,
            odometerIn: isset($data['odometer_in']) ? (string) $data['odometer_in'] : null,
            odometerOut: isset($data['odometer_out']) ? (string) $data['odometer_out'] : null,
            description: $data['description'] ?? null,
            partsCost: (string) $data['parts_cost'],
            labourCost: (string) $data['labour_cost'],
            totalCost: (string) $data['total_cost'],
            technicianNotes: $data['technician_notes'] ?? null,
            customerApproval: (bool) ($data['customer_approval'] ?? false),
            metadata: $data['metadata'] ?? null,
        );

        return response()->json($this->service->create($dto), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');

        try {
            return response()->json($this->service->getById($id, $tenantId));
        } catch (ServiceJobNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(UpdateServiceJobRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $data = $request->validated();

        $dto = new UpdateServiceJobDTO(
            jobType: isset($data['job_type']) ? JobType::from($data['job_type']) : null,
            scheduledAt: isset($data['scheduled_at']) ? new DateTimeImmutable($data['scheduled_at']) : null,
            startedAt: isset($data['started_at']) ? new DateTimeImmutable($data['started_at']) : null,
            completedAt: isset($data['completed_at']) ? new DateTimeImmutable($data['completed_at']) : null,
            odometerIn: array_key_exists('odometer_in', $data) ? ($data['odometer_in'] !== null ? (string) $data['odometer_in'] : null) : null,
            odometerOut: array_key_exists('odometer_out', $data) ? ($data['odometer_out'] !== null ? (string) $data['odometer_out'] : null) : null,
            description: $data['description'] ?? null,
            partsCost: isset($data['parts_cost']) ? (string) $data['parts_cost'] : null,
            labourCost: isset($data['labour_cost']) ? (string) $data['labour_cost'] : null,
            totalCost: isset($data['total_cost']) ? (string) $data['total_cost'] : null,
            technicianNotes: $data['technician_notes'] ?? null,
            customerApproval: isset($data['customer_approval']) ? (bool) $data['customer_approval'] : null,
            metadata: $data['metadata'] ?? null,
        );

        try {
            return response()->json($this->service->update($id, $tenantId, $dto));
        } catch (ServiceJobNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function changeStatus(ChangeServiceJobStatusRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $status = ServiceJobStatus::from($request->validated()['status']);

        try {
            return response()->json($this->service->changeStatus($id, $tenantId, $status));
        } catch (ServiceJobNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');

        try {
            $this->service->delete($id, $tenantId);

            return response()->json(null, 204);
        } catch (ServiceJobNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
