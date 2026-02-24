<?php

namespace Modules\Maintenance\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Maintenance\Application\UseCases\CreateMaintenanceRequestUseCase;
use Modules\Maintenance\Domain\Contracts\MaintenanceRequestRepositoryInterface;
use Modules\Maintenance\Presentation\Requests\StoreMaintenanceRequestRequest;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private MaintenanceRequestRepositoryInterface $requestRepo,
        private CreateMaintenanceRequestUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->requestRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreMaintenanceRequestRequest $request): JsonResponse
    {
        $maintenanceRequest = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($maintenanceRequest, 201);
    }

    public function show(string $id): JsonResponse
    {
        $maintenanceRequest = $this->requestRepo->findById($id);

        if (! $maintenanceRequest) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($maintenanceRequest);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->requestRepo->delete($id);

        return response()->json(null, 204);
    }
}
