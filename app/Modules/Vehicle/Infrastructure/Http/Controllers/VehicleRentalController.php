<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Vehicle\Application\Contracts\CloseVehicleRentalServiceInterface;
use Modules\Vehicle\Application\Contracts\CreateVehicleRentalServiceInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRentalRepositoryInterface;
use Modules\Vehicle\Infrastructure\Http\Requests\CloseVehicleRentalRequest;
use Modules\Vehicle\Infrastructure\Http\Requests\ListVehicleRequest;
use Modules\Vehicle\Infrastructure\Http\Requests\StoreVehicleRentalRequest;
use Modules\Vehicle\Infrastructure\Http\Resources\VehicleRentalResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class VehicleRentalController
{
    public function __construct(
        private readonly CreateVehicleRentalServiceInterface $createVehicleRentalService,
        private readonly CloseVehicleRentalServiceInterface $closeVehicleRentalService,
        private readonly VehicleRentalRepositoryInterface $rentalRepository,
    ) {}

    public function index(ListVehicleRequest $request, int $vehicle): JsonResponse
    {
        $validated = $request->validated();

        $rentals = $this->rentalRepository->paginate(
            tenantId: (int) $validated['tenant_id'],
            vehicleId: $vehicle,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($rentals);
    }

    public function store(StoreVehicleRentalRequest $request): JsonResponse
    {
        $rental = $this->createVehicleRentalService->execute($request->validated());

        return (new VehicleRentalResource($rental))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function close(CloseVehicleRentalRequest $request, int $rental): JsonResponse
    {
        $payload = $request->validated();
        $payload['rental_id'] = $rental;

        $closed = $this->closeVehicleRentalService->execute($payload);

        if (! $closed) {
            return response()->json(['message' => 'Rental not found.'], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->json([], HttpResponse::HTTP_NO_CONTENT);
    }
}
