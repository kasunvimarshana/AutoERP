<?php

namespace Modules\Maintenance\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Maintenance\Application\UseCases\CompleteMaintenanceOrderUseCase;
use Modules\Maintenance\Application\UseCases\CreateMaintenanceOrderUseCase;
use Modules\Maintenance\Application\UseCases\StartMaintenanceOrderUseCase;
use Modules\Maintenance\Domain\Contracts\MaintenanceOrderRepositoryInterface;
use Modules\Maintenance\Presentation\Requests\CompleteMaintenanceOrderRequest;
use Modules\Maintenance\Presentation\Requests\StoreMaintenanceOrderRequest;

class MaintenanceOrderController extends Controller
{
    public function __construct(
        private MaintenanceOrderRepositoryInterface $orderRepo,
        private CreateMaintenanceOrderUseCase       $createUseCase,
        private StartMaintenanceOrderUseCase        $startUseCase,
        private CompleteMaintenanceOrderUseCase     $completeUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->orderRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreMaintenanceOrderRequest $request): JsonResponse
    {
        $order = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($order, 201);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orderRepo->findById($id);

        if (! $order) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($order);
    }

    public function start(string $id): JsonResponse
    {
        $order = $this->startUseCase->execute($id);

        return response()->json($order);
    }

    public function complete(CompleteMaintenanceOrderRequest $request, string $id): JsonResponse
    {
        $order = $this->completeUseCase->execute($id, $request->validated());

        return response()->json($order);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->orderRepo->delete($id);

        return response()->json(null, 204);
    }
}
