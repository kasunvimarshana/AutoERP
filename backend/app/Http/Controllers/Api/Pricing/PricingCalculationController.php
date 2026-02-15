<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\BaseController;
use App\Modules\Pricing\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Pricing Calculation Controller
 * 
 * Handles pricing calculations for products with dynamic rules
 */
class PricingCalculationController extends BaseController
{
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Calculate price for a single product
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.0001',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_group' => 'nullable|string',
            'context' => 'nullable|array',
        ]);

        try {
            $context = array_merge(
                $validated['context'] ?? [],
                array_filter([
                    'customer_id' => $validated['customer_id'] ?? null,
                    'customer_group' => $validated['customer_group'] ?? null,
                ])
            );

            $pricing = $this->pricingService->calculatePrice(
                $validated['product_id'],
                $validated['quantity'],
                $context
            );

            return $this->successResponse($pricing, 'Price calculated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to calculate price: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Calculate bulk pricing for multiple products
     */
    public function calculateBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_group' => 'nullable|string',
            'context' => 'nullable|array',
        ]);

        try {
            $context = array_merge(
                $validated['context'] ?? [],
                array_filter([
                    'customer_id' => $validated['customer_id'] ?? null,
                    'customer_group' => $validated['customer_group'] ?? null,
                ])
            );

            $pricing = $this->pricingService->calculateBulkPricing(
                $validated['items'],
                $context
            );

            return $this->successResponse($pricing, 'Bulk prices calculated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to calculate bulk prices: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get applicable pricing rules for a product
     */
    public function getApplicableRules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'nullable|numeric|min:0.0001',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_group' => 'nullable|string',
        ]);

        try {
            $product = \App\Modules\Product\Models\Product::with(['category'])->findOrFail($validated['product_id']);
            $quantity = $validated['quantity'] ?? 1.0;
            $customerId = $validated['customer_id'] ?? null;
            $customerGroup = $validated['customer_group'] ?? null;

            $rules = \App\Models\PricingRule::query()
                ->active()
                ->currentlyValid()
                ->where(function ($query) use ($product) {
                    $query->where('product_id', $product->id)
                        ->orWhere('category_id', $product->category_id)
                        ->orWhere(function ($q) {
                            $q->whereNull('product_id')
                              ->whereNull('category_id');
                        });
                })
                ->forCustomer($customerId, $customerGroup)
                ->where(function ($query) use ($quantity) {
                    $query->where('min_quantity', '<=', $quantity)
                        ->where(function ($q) use ($quantity) {
                            $q->whereNull('max_quantity')
                              ->orWhere('max_quantity', '>=', $quantity);
                        });
                })
                ->byPriority('desc')
                ->get();

            return $this->successResponse($rules, 'Applicable pricing rules retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get applicable rules: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get applicable discount tiers for a product
     */
    public function getApplicableTiers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'tier_type' => 'nullable|in:buying,selling',
        ]);

        try {
            $tiers = \App\Models\DiscountTier::query()
                ->forProduct($validated['product_id'])
                ->byType($validated['tier_type'] ?? 'selling')
                ->orderByDisplay()
                ->get();

            return $this->successResponse($tiers, 'Discount tiers retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get discount tiers: ' . $e->getMessage(), [], 500);
        }
    }
}
