<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\CreateRentalInspectionServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalInspectionServiceInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalInspectionRepositoryInterface;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalInspectionRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateRentalInspectionRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalInspectionResource;

class RentalInspectionController extends AuthorizedController
{
    public function __construct(
        private readonly RentalInspectionRepositoryInterface $inspectionRepository,
        private readonly CreateRentalInspectionServiceInterface $createService,
        private readonly UpdateRentalInspectionServiceInterface $updateService,
    ) {}

    public function index(int $bookingId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $inspections = $this->inspectionRepository->findByBooking($tenantId, $bookingId);

        return RentalInspectionResource::collection($inspections);
    }

    public function show(int $bookingId, int $id): RentalInspectionResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $inspection = $this->inspectionRepository->findById($tenantId, $id);

        abort_if($inspection === null || $inspection->getRentalBookingId() !== $bookingId, 404, 'Rental inspection not found.');

        return new RentalInspectionResource($inspection);
    }

    public function store(CreateRentalInspectionRequest $request, int $bookingId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'rental_booking_id' => $bookingId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new RentalInspectionResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRentalInspectionRequest $request, int $bookingId, int $id): RentalInspectionResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $inspection = $this->inspectionRepository->findById($tenantId, $id);

        abort_if($inspection === null || $inspection->getRentalBookingId() !== $bookingId, 404, 'Rental inspection not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new RentalInspectionResource($this->updateService->execute($data));
    }

    public function submit(int $bookingId, int $id): RentalInspectionResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $inspection = $this->inspectionRepository->findById($tenantId, $id);

        abort_if($inspection === null || $inspection->getRentalBookingId() !== $bookingId, 404, 'Rental inspection not found.');

        return new RentalInspectionResource($this->updateService->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'inspection_status' => 'submitted',
        ]));
    }

    public function destroy(int $bookingId, int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $inspection = $this->inspectionRepository->findById($tenantId, $id);

        abort_if($inspection === null || $inspection->getRentalBookingId() !== $bookingId, 404, 'Rental inspection not found.');

        $this->inspectionRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
