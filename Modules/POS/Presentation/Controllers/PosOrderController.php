<?php

namespace Modules\POS\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\POS\Application\UseCases\PlaceOrderUseCase;
use Modules\POS\Application\UseCases\RefundOrderUseCase;
use Modules\POS\Domain\Contracts\PosOrderPaymentRepositoryInterface;
use Modules\POS\Infrastructure\Repositories\PosOrderRepository;
use Modules\POS\Presentation\Requests\PlaceOrderRequest;
use Modules\Shared\Application\ResponseFormatter;

class PosOrderController extends Controller
{
    public function __construct(
        private PlaceOrderUseCase $placeUseCase,
        private RefundOrderUseCase $refundUseCase,
        private PosOrderRepository $repo,
        private PosOrderPaymentRepositoryInterface $paymentRepo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->placeUseCase->execute($request->validated());
            return ResponseFormatter::success($order, 'Order placed.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->repo->findById($id);
        if (!$order) {
            return ResponseFormatter::error('Order not found.', [], 404);
        }
        return ResponseFormatter::success($order);
    }

    public function refund(string $id): JsonResponse
    {
        try {
            $order = $this->refundUseCase->execute(['order_id' => $id]);
            return ResponseFormatter::success($order, 'Order refunded.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function payments(string $id): JsonResponse
    {
        $order = $this->repo->findById($id);
        if (!$order) {
            return ResponseFormatter::error('Order not found.', [], 404);
        }
        $payments = $this->paymentRepo->findByOrderId($id);
        return ResponseFormatter::success($payments);
    }
}
