<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\VehicleRental\Application\DTOs\VehicleRentalRecordData;
use Modules\VehicleRental\Application\Services\VehicleRentalService;
use Modules\VehicleRental\Domain\Exceptions\VehicleRentalIntegrityException;
use Modules\VehicleRental\Domain\Exceptions\VehicleRentalRecordNotFoundException;
use Modules\VehicleRental\Presentation\Http\Requests\VehicleRentalRecordRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalRecordResource;

class VehicleRentalResourceController extends Controller
{
    public function __construct(private readonly VehicleRentalService $vehicleRentals) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return VehicleRentalRecordResource::collection(
                $this->vehicleRentals->list($resource, $tenant, $this->filters($request), $this->perPage($request)),
            );
        } catch (VehicleRentalRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(VehicleRentalRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->vehicleRentals->create($resource, VehicleRentalRecordData::fromArray($tenant, $request->validated()));

            return (new VehicleRentalRecordResource($record))->response()->setStatusCode(201);
        } catch (VehicleRentalIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VehicleRentalRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): VehicleRentalRecordResource|JsonResponse
    {
        try {
            return new VehicleRentalRecordResource($this->vehicleRentals->find($resource, $tenant, $id));
        } catch (VehicleRentalRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(VehicleRentalRecordRequest $request, int|string $tenant, string $resource, int|string $id): VehicleRentalRecordResource|JsonResponse
    {
        try {
            return new VehicleRentalRecordResource(
                $this->vehicleRentals->update($resource, $tenant, $id, VehicleRentalRecordData::fromArray($tenant, $request->validated())),
            );
        } catch (VehicleRentalIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VehicleRentalRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->vehicleRentals->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (VehicleRentalIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VehicleRentalRecordNotFoundException $exception) {
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
            'agreement_number',
            'lessor_id',
            'lessee_id',
            'vehicle_id',
            'agreement_type',
            'status',
            'lessor_agreement_id',
            'lessee_agreement_id',
            'driver_id',
            'log_date',
            'entry_date',
            'name',
            'reference',
            'account_id',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(VehicleRentalRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(VehicleRentalIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
