<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\HoldRentalDepositServiceInterface;
use Modules\Rental\Application\Contracts\ReleaseRentalDepositServiceInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDepositRepositoryInterface;
use Modules\Rental\Infrastructure\Http\Requests\HoldRentalDepositRequest;
use Modules\Rental\Infrastructure\Http\Requests\ReleaseRentalDepositRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalDepositResource;

class RentalDepositController extends AuthorizedController
{
    public function __construct(
        private readonly RentalDepositRepositoryInterface $depositRepository,
        private readonly HoldRentalDepositServiceInterface $holdService,
        private readonly ReleaseRentalDepositServiceInterface $releaseService,
    ) {}

    public function index(int $bookingId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');

        $deposits = $this->depositRepository->findByBooking($tenantId, $bookingId);

        return RentalDepositResource::collection($deposits);
    }

    public function show(int $bookingId, int $id): RentalDepositResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $deposit = $this->depositRepository->findById($tenantId, $id);

        abort_if($deposit === null || $deposit->getRentalBookingId() !== $bookingId, 404, 'Deposit not found.');

        return new RentalDepositResource($deposit);
    }

    public function store(HoldRentalDepositRequest $request, int $bookingId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'rental_booking_id' => $bookingId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new RentalDepositResource($this->holdService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function release(ReleaseRentalDepositRequest $request, int $bookingId, int $id): RentalDepositResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $deposit = $this->depositRepository->findById($tenantId, $id);

        abort_if($deposit === null || $deposit->getRentalBookingId() !== $bookingId, 404, 'Deposit not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new RentalDepositResource($this->releaseService->execute($data));
    }

    public function destroy(int $bookingId, int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $deposit = $this->depositRepository->findById($tenantId, $id);

        abort_if($deposit === null || $deposit->getRentalBookingId() !== $bookingId, 404, 'Deposit not found.');

        $this->depositRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
