<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): ResourceCollection
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['status', 'organization_id']);

        return OrderResource::collection($this->orderService->paginate($tenantId, $filters, $perPage));
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['user_id'] = $request->user()->id;

        return (new OrderResource($this->orderService->create($data)))->response()->setStatusCode(201);
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('orders.confirm'), 403);

        return (new OrderResource($this->orderService->confirm($id)))->response();
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('orders.cancel'), 403);

        return (new OrderResource($this->orderService->cancel($id)))->response();
    }
}
