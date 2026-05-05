<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\CancelRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\ConfirmRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\FindRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Infrastructure\Http\Requests\StoreRentalBookingRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalBookingResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RentalBookingController extends AuthorizedController
{
    public function __construct(
        private readonly CreateRentalBookingServiceInterface $createBooking,
        private readonly FindRentalBookingServiceInterface $findBooking,
        private readonly ConfirmRentalBookingServiceInterface $confirmBooking,
        private readonly CancelRentalBookingServiceInterface $cancelBooking,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', RentalBooking::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $result = $this->findBooking->paginate(
            tenantId: $tenantId,
            filters: array_filter([
                'status' => request()->query('status'),
                'customer_id' => request()->query('customer_id'),
            ], static fn (mixed $v): bool => $v !== null),
            perPage: (int) (request()->query('per_page', 15)),
            page: (int) (request()->query('page', 1)),
        );

        return response()->json($result);
    }

    public function store(StoreRentalBookingRequest $request): JsonResponse
    {
        $this->authorize('create', RentalBooking::class);

        $booking = $this->createBooking->execute($request->validated());

        return (new RentalBookingResource($booking))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $booking): JsonResponse
    {
        $this->authorize('view', RentalBooking::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $found = $this->findBooking->findById($tenantId, $booking);

        return (new RentalBookingResource($found))->response();
    }

    public function confirm(int $booking): JsonResponse
    {
        $this->authorize('update', RentalBooking::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $confirmed = $this->confirmBooking->execute($tenantId, $booking);

        return (new RentalBookingResource($confirmed))->response();
    }

    public function cancel(int $booking): JsonResponse
    {
        $this->authorize('update', RentalBooking::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $cancelled = $this->cancelBooking->execute($tenantId, $booking);

        return (new RentalBookingResource($cancelled))->response();
    }
}
