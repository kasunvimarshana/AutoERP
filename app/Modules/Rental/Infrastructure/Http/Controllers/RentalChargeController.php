<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\CreateRentalChargeServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalChargeServiceInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalChargeRepositoryInterface;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalChargeRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateRentalChargeRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalChargeResource;

class RentalChargeController extends AuthorizedController
{
    public function __construct(
        private readonly RentalChargeRepositoryInterface $chargeRepository,
        private readonly CreateRentalChargeServiceInterface $createService,
        private readonly UpdateRentalChargeServiceInterface $updateService,
    ) {}

    public function index(int $bookingId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $charges = $this->chargeRepository->findByBooking($tenantId, $bookingId);

        return RentalChargeResource::collection($charges);
    }

    public function show(int $bookingId, int $id): RentalChargeResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $charge = $this->chargeRepository->findById($tenantId, $id);

        abort_if($charge === null || $charge->getRentalBookingId() !== $bookingId, 404, 'Rental charge not found.');

        return new RentalChargeResource($charge);
    }

    public function store(CreateRentalChargeRequest $request, int $bookingId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'rental_booking_id' => $bookingId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new RentalChargeResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRentalChargeRequest $request, int $bookingId, int $id): RentalChargeResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $charge = $this->chargeRepository->findById($tenantId, $id);

        abort_if($charge === null || $charge->getRentalBookingId() !== $bookingId, 404, 'Rental charge not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new RentalChargeResource($this->updateService->execute($data));
    }

    public function post(int $bookingId, int $id): RentalChargeResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $charge = $this->chargeRepository->findById($tenantId, $id);

        abort_if($charge === null || $charge->getRentalBookingId() !== $bookingId, 404, 'Rental charge not found.');

        return new RentalChargeResource($this->updateService->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'status' => 'posted',
        ]));
    }

    public function destroy(int $bookingId, int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $charge = $this->chargeRepository->findById($tenantId, $id);

        abort_if($charge === null || $charge->getRentalBookingId() !== $bookingId, 404, 'Rental charge not found.');

        $this->chargeRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
