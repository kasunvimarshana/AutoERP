<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Order\Commands\CancelOrderCommand;
use App\Application\Order\Commands\PlaceOrderCommand;
use App\Application\Order\Handlers\CancelOrderHandler;
use App\Application\Order\Handlers\GetOrderQueryHandler;
use App\Application\Order\Handlers\PlaceOrderHandler;
use App\Application\Order\Queries\GetOrderQuery;
use App\Domain\Order\Exceptions\OrderException;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\Http\Requests\PlaceOrderRequest;
use App\Presentation\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * OrderController — thin HTTP adapter.
 *
 * Responsibilities:
 *   - Extract data from the HTTP request
 *   - Build Command / Query objects
 *   - Dispatch to the appropriate Handler
 *   - Map the result to a JSON response
 *
 * This class contains ZERO business logic.
 */
final class OrderController extends Controller
{
    public function __construct(
        private readonly PlaceOrderHandler     $placeHandler,
        private readonly CancelOrderHandler    $cancelHandler,
        private readonly GetOrderQueryHandler  $queryHandler,
    ) {
    }

    /**
     * POST /api/orders
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        try {
            $orderId = $this->placeHandler->handle(
                PlaceOrderCommand::fromArray($request->validated())
            );

            return response()->json([
                'message'  => 'Order placed successfully.',
                'order_id' => $orderId,
            ], 201);

        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/orders/{id}
     */
    public function show(string $id): JsonResponse
    {
        try {
            $order = $this->queryHandler->handle(new GetOrderQuery($id));
            return response()->json(['data' => new OrderResource($order)]);

        } catch (OrderException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * POST /api/orders/{id}/cancel
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $this->cancelHandler->handle(new CancelOrderCommand(
                orderId: $id,
                reason: request()->input('reason', 'No reason provided.'),
            ));

            return response()->json(['message' => 'Order cancelled successfully.']);

        } catch (OrderException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 422;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }
}
