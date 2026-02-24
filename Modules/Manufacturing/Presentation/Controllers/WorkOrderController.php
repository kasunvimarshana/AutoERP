<?php

namespace Modules\Manufacturing\Presentation\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Application\UseCases\CompleteWorkOrderUseCase;
use Modules\Manufacturing\Application\UseCases\CreateWorkOrderUseCase;
use Modules\Manufacturing\Application\UseCases\StartWorkOrderUseCase;
use Modules\Manufacturing\Infrastructure\Repositories\WorkOrderRepository;
use Modules\Manufacturing\Presentation\Requests\CompleteWorkOrderRequest;
use Modules\Manufacturing\Presentation\Requests\StoreWorkOrderRequest;
use Modules\Shared\Application\ResponseFormatter;

class WorkOrderController extends Controller
{
    public function __construct(
        private CreateWorkOrderUseCase   $createUseCase,
        private StartWorkOrderUseCase    $startUseCase,
        private CompleteWorkOrderUseCase $completeUseCase,
        private WorkOrderRepository      $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        try {
            $workOrder = $this->createUseCase->execute($request->validated());
            return ResponseFormatter::success($workOrder, 'Work order created.', 201);
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $workOrder = $this->repo->findById($id);
        if (! $workOrder) {
            return ResponseFormatter::error('Work order not found.', [], 404);
        }
        return ResponseFormatter::success($workOrder->load('lines'));
    }

    public function update(StoreWorkOrderRequest $request, string $id): JsonResponse
    {
        try {
            $workOrder = $this->repo->update($id, $request->validated());
            return ResponseFormatter::success($workOrder, 'Work order updated.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::error('Work order not found.', [], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Work order deleted.');
    }

    public function start(string $id): JsonResponse
    {
        try {
            $workOrder = $this->startUseCase->execute($id);
            return ResponseFormatter::success($workOrder, 'Work order started.');
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function complete(string $id, CompleteWorkOrderRequest $request): JsonResponse
    {
        try {
            $workOrder = $this->completeUseCase->execute($id, $request->validated());
            return ResponseFormatter::success($workOrder, 'Work order completed.');
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }
}
