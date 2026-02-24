<?php

namespace Modules\ECommerce\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ECommerce\Application\UseCases\ConfirmECommerceOrderUseCase;
use Modules\ECommerce\Application\UseCases\PlaceECommerceOrderUseCase;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderRepositoryInterface;
use Modules\ECommerce\Presentation\Requests\PlaceECommerceOrderRequest;

class ECommerceOrderController extends Controller
{
    public function __construct(
        private ECommerceOrderRepositoryInterface $orderRepo,
        private PlaceECommerceOrderUseCase        $placeUseCase,
        private ConfirmECommerceOrderUseCase      $confirmUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->orderRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(PlaceECommerceOrderRequest $request): JsonResponse
    {
        $order = $this->placeUseCase->execute(
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

    public function confirm(string $id): JsonResponse
    {
        $order = $this->confirmUseCase->execute($id);

        return response()->json($order);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->orderRepo->delete($id);

        return response()->json(null, 204);
    }
}
