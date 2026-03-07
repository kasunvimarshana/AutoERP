<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the orders.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getAllOrders($request->all());
        return OrderResource::collection($orders)->response();
    }

    /**
     * Store a newly created order.
     * Demonstrates cross-service validation and Saga triggering.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        // Extract the user's JWT from Keycloak so we can forward it to ProductService
        $jwt = $request->bearerToken();

        $order = $this->orderService->createOrder($request->validated(), $jwt);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
