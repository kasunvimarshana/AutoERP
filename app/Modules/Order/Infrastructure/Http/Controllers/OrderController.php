<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Order\Application\Contracts\OrderServiceInterface;
use Modules\Order\Application\DTOs\OrderData;
use Modules\Order\Infrastructure\Http\Requests\StoreOrderRequest;
use Modules\Order\Infrastructure\Http\Requests\UpdateOrderRequest;
use Modules\Order\Infrastructure\Http\Resources\OrderResource;

/**
 * @OA\Tag(name="Orders", description="Order management (Purchase & Sales Orders)")
 */
final class OrderController extends AuthorizedController
{
    public function __construct(private readonly OrderServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/order/orders",
     *     tags={"Orders"},
     *     summary="List orders",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"purchase","sale"})),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","confirmed","in_progress","completed","cancelled","on_hold"})),
     *     @OA\Parameter(name="payment_status", in="query", @OA\Schema(type="string", enum={"pending","partial","paid","overpaid","refunded"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of orders")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) $request->query('per_page', 15);
        $filters  = array_filter(
            $request->only(['type', 'status', 'payment_status', 'supplier_id', 'customer_id']),
            static fn ($v) => $v !== null && $v !== ''
        );
        $filters['tenant_id'] = $tenantId;

        return OrderResource::collection(
            $this->service->list($filters, $perPage)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/order/orders",
     *     tags={"Orders"},
     *     summary="Create an order",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreOrderRequest")),
     *     @OA\Response(response=201, description="Order created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = OrderData::fromArray($request->validated());
        $order    = $this->service->create($dto, $tenantId);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/order/orders/{id}",
     *     tags={"Orders"},
     *     summary="Get an order by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        return (new OrderResource($this->service->find($id)))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/order/orders/{id}",
     *     tags={"Orders"},
     *     summary="Update an order",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateOrderRequest")),
     *     @OA\Response(response=200, description="Order updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $dto   = OrderData::fromArray($request->validated());
        $order = $this->service->update($id, $dto);

        return (new OrderResource($order))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/order/orders/{id}",
     *     tags={"Orders"},
     *     summary="Delete an order",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    /**
     * @OA\Patch(
     *     path="/api/order/orders/{id}/status",
     *     tags={"Orders"},
     *     summary="Update order status",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"status"},
     *         @OA\Property(property="status", type="string", enum={"draft","confirmed","in_progress","completed","cancelled","on_hold"})
     *     )),
     *     @OA\Response(response=200, description="Status updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:draft,confirmed,in_progress,completed,cancelled,on_hold'],
        ]);

        $tenantId = (int) $request->header('X-Tenant-ID');
        $order    = $this->service->updateStatus($id, $request->input('status'), $tenantId);

        return (new OrderResource($order))->response();
    }
}
