<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\VehicleService\Application\DTOs\VehicleServiceRecordData;
use Modules\VehicleService\Application\Services\VehicleServiceService;
use Modules\VehicleService\Domain\Exceptions\VehicleServiceIntegrityException;
use Modules\VehicleService\Domain\Exceptions\VehicleServiceRecordNotFoundException;
use Modules\VehicleService\Presentation\Http\Requests\VehicleServiceRecordRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceRecordResource;

class VehicleServiceResourceController extends Controller
{
    public function __construct(private readonly VehicleServiceService $vehicleServices) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return VehicleServiceRecordResource::collection(
                $this->vehicleServices->list($resource, $tenant, $this->filters($request), $this->perPage($request)),
            );
        } catch (VehicleServiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(VehicleServiceRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->vehicleServices->create($resource, VehicleServiceRecordData::fromArray($tenant, $request->validated()));

            return (new VehicleServiceRecordResource($record))->response()->setStatusCode(201);
        } catch (VehicleServiceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VehicleServiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): VehicleServiceRecordResource|JsonResponse
    {
        try {
            return new VehicleServiceRecordResource($this->vehicleServices->find($resource, $tenant, $id));
        } catch (VehicleServiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(VehicleServiceRecordRequest $request, int|string $tenant, string $resource, int|string $id): VehicleServiceRecordResource|JsonResponse
    {
        try {
            return new VehicleServiceRecordResource(
                $this->vehicleServices->update($resource, $tenant, $id, VehicleServiceRecordData::fromArray($tenant, $request->validated())),
            );
        } catch (VehicleServiceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VehicleServiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->vehicleServices->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (VehicleServiceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VehicleServiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return collect($request->only([
            'organization_unit_id',
            'parent_id',
            'service_type_id',
            'job_card_id',
            'labor_item_id',
            'diagnostic_id',
            'inspection_id',
            'customer_id',
            'vehicle_id',
            'warehouse_id',
            'item_id',
            'variant_id',
            'employee_id',
            'assigned_to',
            'status',
            'priority',
            'reference',
            'job_card_number',
            'diagnostic_number',
            'inspection_number',
            'diagnostic_phase',
            'inspection_phase',
            'overall_result',
            'result',
            'severity',
            'is_active',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(VehicleServiceRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(VehicleServiceIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
