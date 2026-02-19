<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Requests\CalculatePriceRequest;
use Modules\Pricing\Services\PricingService;

class PricingController extends Controller
{
    public function __construct(
        private readonly PricingService $pricingService
    ) {}

    public function calculate(CalculatePriceRequest $request): JsonResponse
    {
        $result = $this->pricingService->calculatePrice(
            $request->integer('product_id'),
            (string) $request->input('quantity', '1'),
            $request->except('product_id', 'quantity')
        );

        return $this->successResponse($result, 'Price calculated successfully');
    }

    public function calculateCart(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'sometimes|numeric|min:0.01',
        ]);

        $result = $this->pricingService->calculateCartPrice(
            $request->input('items'),
            $request->except('items')
        );

        return $this->successResponse($result, 'Cart price calculated successfully');
    }

    public function strategies(): JsonResponse
    {
        $strategies = $this->pricingService->getAvailableStrategies();

        return $this->successResponse($strategies, 'Pricing strategies retrieved successfully');
    }
}
