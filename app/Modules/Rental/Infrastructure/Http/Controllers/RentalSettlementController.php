<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\CreateRentalSettlementServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalSettlementServiceInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalSettlementRepositoryInterface;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalSettlementRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateRentalSettlementRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalSettlementResource;

class RentalSettlementController extends AuthorizedController
{
    public function __construct(
        private readonly RentalSettlementRepositoryInterface $settlementRepository,
        private readonly CreateRentalSettlementServiceInterface $createService,
        private readonly UpdateRentalSettlementServiceInterface $updateService,
    ) {}

    public function index(int $bookingId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $settlements = $this->settlementRepository->findByBooking($tenantId, $bookingId);

        return RentalSettlementResource::collection($settlements);
    }

    public function show(int $bookingId, int $id): RentalSettlementResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $settlement = $this->settlementRepository->findById($tenantId, $id);

        abort_if($settlement === null || $settlement->getRentalBookingId() !== $bookingId, 404, 'Rental settlement not found.');

        return new RentalSettlementResource($settlement);
    }

    public function store(CreateRentalSettlementRequest $request, int $bookingId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'rental_booking_id' => $bookingId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new RentalSettlementResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRentalSettlementRequest $request, int $bookingId, int $id): RentalSettlementResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $settlement = $this->settlementRepository->findById($tenantId, $id);

        abort_if($settlement === null || $settlement->getRentalBookingId() !== $bookingId, 404, 'Rental settlement not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new RentalSettlementResource($this->updateService->execute($data));
    }

    public function approve(int $bookingId, int $id): RentalSettlementResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $settlement = $this->settlementRepository->findById($tenantId, $id);

        abort_if($settlement === null || $settlement->getRentalBookingId() !== $bookingId, 404, 'Rental settlement not found.');

        return new RentalSettlementResource($this->updateService->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'status' => 'approved',
        ]));
    }

    public function destroy(int $bookingId, int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $settlement = $this->settlementRepository->findById($tenantId, $id);

        abort_if($settlement === null || $settlement->getRentalBookingId() !== $bookingId, 404, 'Rental settlement not found.');

        $this->settlementRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
