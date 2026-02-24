<?php

namespace Modules\Logistics\Presentation\Controllers;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Logistics\Application\UseCases\CompleteDeliveryUseCase;
use Modules\Logistics\Application\UseCases\CreateDeliveryOrderUseCase;
use Modules\Logistics\Application\UseCases\DispatchDeliveryUseCase;
use Modules\Logistics\Domain\Contracts\TrackingEventRepositoryInterface;
use Modules\Logistics\Infrastructure\Repositories\DeliveryOrderRepository;
use Modules\Logistics\Presentation\Requests\StoreDeliveryOrderRequest;
use Modules\Shared\Application\ResponseFormatter;

class DeliveryOrderController extends Controller
{
    public function __construct(
        private CreateDeliveryOrderUseCase       $createUseCase,
        private DispatchDeliveryUseCase          $dispatchUseCase,
        private CompleteDeliveryUseCase          $completeUseCase,
        private DeliveryOrderRepository          $repo,
        private TrackingEventRepositoryInterface $trackingRepo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreDeliveryOrderRequest $request): JsonResponse
    {
        $order = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($order, 'Delivery order created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->repo->findById($id);
        if (! $order) {
            return ResponseFormatter::error('Delivery order not found.', [], 404);
        }
        return ResponseFormatter::success($order);
    }

    public function dispatch(string $id): JsonResponse
    {
        try {
            $order = $this->dispatchUseCase->execute($id);
            return ResponseFormatter::success($order, 'Delivery order dispatched.');
        } catch (ModelNotFoundException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 404);
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function complete(string $id): JsonResponse
    {
        try {
            $order = $this->completeUseCase->execute($id);
            return ResponseFormatter::success($order, 'Delivery order marked as delivered.');
        } catch (ModelNotFoundException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 404);
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function tracking(string $id): JsonResponse
    {
        $order = $this->repo->findById($id);
        if (! $order) {
            return ResponseFormatter::error('Delivery order not found.', [], 404);
        }
        $events = $this->trackingRepo->findByDeliveryOrder($id);
        return ResponseFormatter::success($events);
    }

    public function update(StoreDeliveryOrderRequest $request, string $id): JsonResponse
    {
        $order = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($order, 'Delivery order updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Delivery order deleted.');
    }
}
