<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Contracts\Services\DiscountServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceResolverServiceInterface;
use Modules\Pricing\Presentation\Http\Requests\PreviewDiscountCalculationRequest;

final class PriceResolverController extends Controller
{
    public function __construct(
        private readonly PriceResolverServiceInterface $priceResolverService,
        private readonly DiscountServiceInterface $discountService,
    ) {}

    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'min:1'],
            'item_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $result = $this->priceResolverService->resolvePrice($validated);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json($result->valueOrFail());
    }

    public function previewDiscountCalculation(PreviewDiscountCalculationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $baseAmount = (float) $validated['base_amount'];
        $quantity = (float) ($validated['quantity'] ?? 1);
        $discounts = $this->discountInputs($validated);

        $result = $this->discountService->resolveDiscounts($discounts, $baseAmount, $quantity);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $preview = $result->valueOrFail();
        $discountAmount = (float) ($preview['discount_amount'] ?? 0);

        return response()->json([
            'tenant_id' => (int) $validated['tenant_id'],
            'base_amount' => round($baseAmount, 4),
            'quantity' => round($quantity, 4),
            'applied_discounts' => $preview['applied_discounts'] ?? [],
            'discount_amount' => round($discountAmount, 4),
            'discount_percentage' => (float) ($preview['discount_percentage'] ?? 0),
            'net_amount' => round(max(0, $baseAmount - $discountAmount), 4),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array<string, mixed>>
     */
    private function discountInputs(array $validated): array
    {
        if (isset($validated['discounts']) && is_array($validated['discounts'])) {
            return array_values(array_filter(
                $validated['discounts'],
                static fn (mixed $discount): bool => is_array($discount),
            ));
        }

        return [[
            'discount_type' => $validated['discount_type'] ?? 'percentage',
            'discount_value' => $validated['discount_value'] ?? 0,
            'priority' => 0,
            'is_stackable' => true,
            'is_exclusive' => false,
        ]];
    }
}
