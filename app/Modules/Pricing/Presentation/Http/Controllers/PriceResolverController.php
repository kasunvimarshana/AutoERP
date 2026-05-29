<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Pricing\Application\Contracts\Services\DiscountServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceResolverServiceInterface;
use Modules\Pricing\Presentation\Http\Requests\PreviewDiscountCalculationRequest;

final class PriceResolverController extends Controller
{
    public function __construct(
        private readonly PriceResolverServiceInterface $priceResolverService,
        private readonly DiscountServiceInterface $discountService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'price_list_id' => ['nullable', 'integer', 'min:1'],
            'party_type' => ['nullable', 'string', 'in:customer,supplier'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', 'max:120'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'date' => ['nullable', 'date'],
        ]);
        $validated['tenant_id'] = (int) ($validated['tenant_id'] ?? $this->currentTenant->currentTenantId());

        $result = $this->priceResolverService->resolvePrice($validated);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $resolved = $result->valueOrFail();
        $payload = $resolved instanceof DataRecord ? $resolved->toArray() : (array) $resolved;

        return response()->json([
            'input' => [
                'tenant_id' => $validated['tenant_id'],
                'item_id' => (int) $validated['item_id'],
                'quantity' => (float) $validated['quantity'],
                'uom_id' => isset($validated['uom_id']) ? (int) $validated['uom_id'] : null,
                'currency_id' => isset($validated['currency_id']) ? (int) $validated['currency_id'] : null,
                'price_list_id' => isset($validated['price_list_id']) ? (int) $validated['price_list_id'] : null,
                'party_type' => $validated['party_type'] ?? null,
                'party_id' => isset($validated['party_id']) ? (int) $validated['party_id'] : null,
                'source_type' => $validated['source_type'] ?? null,
                'source_id' => isset($validated['source_id']) ? (int) $validated['source_id'] : null,
                'date' => $validated['date'] ?? null,
            ],
            'calculated' => [
                'resolved_unit_price' => $payload['base_unit_price'] ?? null,
                'base_amount' => $payload['base_amount'] ?? null,
                'discount_amount' => $payload['discount_amount'] ?? null,
                'discount_percentage' => $payload['discount_percentage'] ?? null,
                'net_amount' => $payload['final_amount'] ?? null,
                'price_list_id' => $payload['price_list_id'] ?? null,
                'price_list_item_id' => $payload['price_list_item_id'] ?? null,
                'resolved_quantity' => $payload['resolved_quantity'] ?? null,
            ],
            'breakdown' => [
                ['label' => 'price_list', 'value' => $payload['applied_price_list'] ?? null],
                ['label' => 'price_item', 'value' => $payload['applied_price_item'] ?? null],
                ['label' => 'tier', 'value' => $payload['applied_tier'] ?? null],
                ['label' => 'discounts', 'value' => $payload['applied_discounts'] ?? []],
                ['label' => 'explanation', 'value' => $payload['explanation'] ?? []],
            ],
            'warnings' => [],
            'errors' => [],
        ]);
    }

    public function previewDiscountCalculation(PreviewDiscountCalculationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['tenant_id'] = (int) ($validated['tenant_id'] ?? $this->currentTenant->currentTenantId());
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
            'input' => [
                'tenant_id' => (int) $validated['tenant_id'],
                'base_amount' => round($baseAmount, 4),
                'quantity' => round($quantity, 4),
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'discounts' => $discounts,
            ],
            'calculated' => [
                'applied_discounts' => $preview['applied_discounts'] ?? [],
                'discount_amount' => round($discountAmount, 4),
                'discount_percentage' => (float) ($preview['discount_percentage'] ?? 0),
                'net_amount' => round(max(0, $baseAmount - $discountAmount), 4),
            ],
            'breakdown' => [
                ['label' => 'discount_priority', 'value' => 'Backend discount service sorted and applied discounts.'],
            ],
            'warnings' => [],
            'errors' => [],
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
