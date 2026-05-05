<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\CreateRentalIncidentServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalIncidentServiceInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalIncidentRepositoryInterface;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalIncidentRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateRentalIncidentRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalIncidentResource;

class RentalIncidentController extends AuthorizedController
{
    public function __construct(
        private readonly RentalIncidentRepositoryInterface $incidentRepository,
        private readonly CreateRentalIncidentServiceInterface $createService,
        private readonly UpdateRentalIncidentServiceInterface $updateService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $orgUnitId = request()->integer('org_unit_id') ?: null;

        $incidents = $this->incidentRepository->findByTenant(
            $tenantId,
            $orgUnitId,
            request()->only(['status', 'incident_type', 'asset_id']),
        );

        return RentalIncidentResource::collection($incidents);
    }

    public function show(int $id): RentalIncidentResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $incident = $this->incidentRepository->findById($tenantId, $id);

        abort_if($incident === null, 404, 'Rental incident not found.');

        return new RentalIncidentResource($incident);
    }

    public function store(CreateRentalIncidentRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'reported_by' => $request->user()?->id,
            'changed_by' => $request->user()?->id,
        ]);

        return (new RentalIncidentResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRentalIncidentRequest $request, int $id): RentalIncidentResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $incident = $this->incidentRepository->findById($tenantId, $id);

        abort_if($incident === null, 404, 'Rental incident not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new RentalIncidentResource($this->updateService->execute($data));
    }

    public function destroy(int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $incident = $this->incidentRepository->findById($tenantId, $id);

        abort_if($incident === null, 404, 'Rental incident not found.');

        $this->incidentRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
