<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Vehicle\Application\Contracts\CreateVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\FindVehicleServiceInterface;
use Modules\Vehicle\Application\Services\DeleteVehicleService;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Infrastructure\Http\Requests\ListVehicleRequest;
use Modules\Vehicle\Infrastructure\Http\Requests\StoreVehicleRequest;
use Modules\Vehicle\Infrastructure\Http\Resources\VehicleCollection;
use Modules\Vehicle\Infrastructure\Http\Resources\VehicleResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class VehicleController extends AuthorizedController
{
    public function __construct(
        private readonly CreateVehicleServiceInterface $createVehicleService,
        private readonly FindVehicleServiceInterface $findVehicleService,
        private readonly DeleteVehicleService $deleteVehicleService,
        private readonly VehicleDocumentRepositoryInterface $documentRepository,
    ) {}

    public function index(ListVehicleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'],
            'ownership_type' => $validated['ownership_type'] ?? null,
            'rental_status' => $validated['rental_status'] ?? null,
            'service_status' => $validated['service_status'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'make' => $validated['make'] ?? null,
            'model' => $validated['model'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $vehicles = $this->findVehicleService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new VehicleCollection($vehicles))->response();
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $vehicle = $this->createVehicleService->execute($payload);

        foreach (($payload['documents'] ?? []) as $document) {
            $this->documentRepository->upsertByType(
                tenantId: (int) $payload['tenant_id'],
                vehicleId: (int) $vehicle->getId(),
                documentType: (string) $document['document_type'],
                data: $document,
            );
        }

        return (new VehicleResource($vehicle))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(ListVehicleRequest $request, int $vehicle): VehicleResource
    {
        $tenantId = (int) $request->validated('tenant_id');
        $found = $this->findVehicleService->find($vehicle);

        if ($found === null || $found->getTenantId() !== $tenantId) {
            abort(HttpResponse::HTTP_NOT_FOUND, 'Vehicle not found.');
        }

        return new VehicleResource($found);
    }

    public function destroy(ListVehicleRequest $request, int $vehicle): JsonResponse
    {
        $tenantId = (int) $request->validated('tenant_id');
        $found = $this->findVehicleService->find($vehicle);

        if ($found === null || $found->getTenantId() !== $tenantId) {
            return response()->json(['message' => 'Vehicle not found.'], HttpResponse::HTTP_NOT_FOUND);
        }

        $this->deleteVehicleService->execute([
            'tenant_id' => $tenantId,
            'vehicle_id' => $vehicle,
        ]);

        return Response::json([], HttpResponse::HTTP_NO_CONTENT);
    }
}
