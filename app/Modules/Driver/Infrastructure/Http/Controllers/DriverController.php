<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Driver\Application\Contracts\DriverServiceInterface;
use Modules\Driver\Application\DTOs\CreateDriverDTO;
use Modules\Driver\Application\DTOs\UpdateDriverDTO;
use Modules\Driver\Domain\Exceptions\DriverNotFoundException;
use Modules\Driver\Domain\ValueObjects\CompensationType;
use Modules\Driver\Domain\ValueObjects\DriverStatus;
use Modules\Driver\Infrastructure\Http\Requests\ChangeDriverStatusRequest;
use Modules\Driver\Infrastructure\Http\Requests\CreateDriverRequest;
use Modules\Driver\Infrastructure\Http\Requests\UpdateDriverRequest;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = (int) $request->header('X-Tenant-ID');
        $orgUnitId = $request->query('org_unit_id') ? (int) $request->query('org_unit_id') : null;
        $drivers   = $this->service->listByTenant($tenantId, $orgUnitId);

        return response()->json(['data' => array_map(fn ($d) => $this->toArray($d), $drivers)]);
    }

    public function available(Request $request): JsonResponse
    {
        $tenantId  = (int) $request->header('X-Tenant-ID');
        $orgUnitId = $request->query('org_unit_id') ? (int) $request->query('org_unit_id') : null;
        $drivers   = $this->service->listAvailableForTrip($tenantId, $orgUnitId);

        return response()->json(['data' => array_map(fn ($d) => $this->toArray($d), $drivers)]);
    }

    public function store(CreateDriverRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v        = $request->validated();

        $dto = new CreateDriverDTO(
            tenantId:         $tenantId,
            orgUnitId:        isset($v['org_unit_id']) ? (int) $v['org_unit_id'] : null,
            employeeId:       isset($v['employee_id']) ? (int) $v['employee_id'] : null,
            driverCode:       $v['driver_code'],
            fullName:         $v['full_name'],
            phone:            $v['phone'] ?? null,
            email:            $v['email'] ?? null,
            address:          $v['address'] ?? null,
            compensationType: CompensationType::from($v['compensation_type']),
            perTripRate:      isset($v['per_trip_rate']) ? (string) $v['per_trip_rate'] : '0.000000',
            commissionPct:    isset($v['commission_pct']) ? (string) $v['commission_pct'] : '0.00',
            metadata:         $v['metadata'] ?? null,
        );

        $driver = $this->service->create($dto);

        return response()->json(['data' => $this->toArray($driver)], 201);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $driver = $this->service->getById($id);
        } catch (DriverNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($driver)]);
    }

    public function update(UpdateDriverRequest $request, int $id): JsonResponse
    {
        try {
            $v      = $request->validated();
            $dto    = new UpdateDriverDTO(
                fullName:         $v['full_name'],
                phone:            $v['phone'] ?? null,
                email:            $v['email'] ?? null,
                address:          $v['address'] ?? null,
                compensationType: CompensationType::from($v['compensation_type']),
                perTripRate:      isset($v['per_trip_rate']) ? (string) $v['per_trip_rate'] : '0.000000',
                commissionPct:    isset($v['commission_pct']) ? (string) $v['commission_pct'] : '0.00',
                metadata:         $v['metadata'] ?? null,
                isActive:         (bool) $v['is_active'],
            );
            $driver = $this->service->update($id, $dto);
        } catch (DriverNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($driver)]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (DriverNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(null, 204);
    }

    public function changeStatus(ChangeDriverStatusRequest $request, int $id): JsonResponse
    {
        try {
            $status = DriverStatus::from($request->validated()['status']);
            $driver = $this->service->changeStatus($id, $status);
        } catch (DriverNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($driver)]);
    }

    private function toArray(object $driver): array
    {
        return [
            'id'               => $driver->id,
            'tenant_id'        => $driver->tenantId,
            'org_unit_id'      => $driver->orgUnitId,
            'employee_id'      => $driver->employeeId,
            'driver_code'      => $driver->driverCode,
            'full_name'        => $driver->fullName,
            'phone'            => $driver->phone,
            'email'            => $driver->email,
            'address'          => $driver->address,
            'compensation_type'=> $driver->compensationType->value,
            'per_trip_rate'    => $driver->perTripRate,
            'commission_pct'   => $driver->commissionPct,
            'status'           => $driver->status->value,
            'is_active'        => $driver->isActive,
        ];
    }
}
