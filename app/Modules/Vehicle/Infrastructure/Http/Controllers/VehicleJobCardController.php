<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Vehicle\Application\Contracts\CreateVehicleJobCardServiceInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleJobCardRepositoryInterface;
use Modules\Vehicle\Infrastructure\Http\Requests\ListVehicleRequest;
use Modules\Vehicle\Infrastructure\Http\Requests\StoreVehicleJobCardRequest;
use Modules\Vehicle\Infrastructure\Http\Resources\VehicleJobCardResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class VehicleJobCardController
{
    public function __construct(
        private readonly CreateVehicleJobCardServiceInterface $createVehicleJobCardService,
        private readonly VehicleJobCardRepositoryInterface $jobCardRepository,
    ) {}

    public function index(ListVehicleRequest $request, int $vehicle): JsonResponse
    {
        $validated = $request->validated();

        $jobCards = $this->jobCardRepository->paginate(
            tenantId: (int) $validated['tenant_id'],
            vehicleId: $vehicle,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($jobCards);
    }

    public function store(StoreVehicleJobCardRequest $request): JsonResponse
    {
        $jobCard = $this->createVehicleJobCardService->execute($request->validated());

        return (new VehicleJobCardResource($jobCard))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }
}
