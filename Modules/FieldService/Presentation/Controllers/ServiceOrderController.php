<?php

namespace Modules\FieldService\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\FieldService\Application\UseCases\AssignTechnicianUseCase;
use Modules\FieldService\Application\UseCases\CompleteServiceOrderUseCase;
use Modules\FieldService\Application\UseCases\CreateServiceOrderUseCase;
use Modules\FieldService\Domain\Contracts\ServiceOrderRepositoryInterface;
use Modules\FieldService\Presentation\Requests\AssignTechnicianRequest;
use Modules\FieldService\Presentation\Requests\CompleteServiceOrderRequest;
use Modules\FieldService\Presentation\Requests\StoreServiceOrderRequest;

class ServiceOrderController extends Controller
{
    public function __construct(
        private ServiceOrderRepositoryInterface $orderRepo,
        private CreateServiceOrderUseCase       $createUseCase,
        private AssignTechnicianUseCase         $assignUseCase,
        private CompleteServiceOrderUseCase     $completeUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->orderRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreServiceOrderRequest $request): JsonResponse
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

    public function assign(AssignTechnicianRequest $request, string $id): JsonResponse
    {
        $order = $this->assignUseCase->execute($id, $request->validated()['technician_id']);

        return response()->json($order);
    }

    public function complete(CompleteServiceOrderRequest $request, string $id): JsonResponse
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
