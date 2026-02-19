<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Core\Helpers\MathHelper;
use Modules\Pricing\Http\Requests\CalculatePriceRequest;
use Modules\Pricing\Http\Requests\StorePriceRequest;
use Modules\Pricing\Http\Requests\UpdatePriceRequest;
use Modules\Pricing\Http\Resources\PriceCalculationResource;
use Modules\Pricing\Http\Resources\ProductPriceResource;
use Modules\Pricing\Models\ProductPrice;
use Modules\Pricing\Repositories\ProductPriceRepository;
use Modules\Pricing\Services\PriceManagementService;
use Modules\Pricing\Services\PricingService;
use Modules\Product\Models\Product;

/**
 * PricingController
 *
 * Manages product prices and price calculations
 */
class PricingController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
        protected ProductPriceRepository $priceRepository,
        protected PriceManagementService $priceManagementService
    ) {}

    /**
     * List prices for a product
     */
    public function index(Product $product): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductPrice::class);

        $prices = $this->priceRepository->getByProduct($product->id);

        return ProductPriceResource::collection($prices);
    }

    /**
     * Create a new price for a product
     */
    public function store(StorePriceRequest $request, Product $product): JsonResponse
    {
        $this->authorize('create', ProductPrice::class);

        $data = array_merge($request->validated(), [
            'tenant_id' => auth()->user()->tenant_id,
            'product_id' => $product->id,
        ]);

        $price = $this->priceManagementService->createPrice($data);

        return (new ProductPriceResource($price))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a specific price
     */
    public function show(Product $product, ProductPrice $price): ProductPriceResource
    {
        $this->authorize('view', $price);

        if ($price->product_id !== $product->id) {
            abort(404, 'Price not found for this product');
        }

        return new ProductPriceResource($price->load(['product', 'location']));
    }

    /**
     * Update a price
     */
    public function update(UpdatePriceRequest $request, Product $product, ProductPrice $price): ProductPriceResource
    {
        $this->authorize('update', $price);

        if ($price->product_id !== $product->id) {
            abort(404, 'Price not found for this product');
        }

        $price = $this->priceManagementService->updatePrice($price->id, $request->validated());

        return new ProductPriceResource($price);
    }

    /**
     * Delete a price
     */
    public function destroy(Product $product, ProductPrice $price): JsonResponse
    {
        $this->authorize('delete', $price);

        if ($price->product_id !== $product->id) {
            abort(404, 'Price not found for this product');
        }

        $this->priceManagementService->deletePrice($price->id);

        return response()->json(null, 204);
    }

    /**
     * Calculate price for product with quantity and location
     */
    public function calculate(CalculatePriceRequest $request): PriceCalculationResource
    {
        $this->authorize('calculate', ProductPrice::class);

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        $locationId = $request->input('location_id');
        $date = $request->input('date');
        $additionalContext = $request->input('context', []);

        $product = Product::findOrFail($productId);

        $price = $this->priceRepository->findActivePriceForCalculation($productId, $locationId, $date);

        if (! $price) {
            abort(404, 'No active price found for this product');
        }

        $context = array_merge(
            $price->config ?? [],
            $additionalContext,
            [
                'location_id' => $locationId,
                'date' => $date,
            ]
        );

        $totalPrice = $this->pricingService->calculate(
            $price->strategy,
            $price->price,
            $quantity,
            $context
        );

        $unitPrice = MathHelper::divide($totalPrice, $quantity);

        $breakdown = $this->generateBreakdown(
            $price->strategy->value,
            $price->price,
            $unitPrice,
            $totalPrice,
            $quantity,
            $context
        );

        $result = [
            'product_id' => $product->id,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ],
            'quantity' => $quantity,
            'location_id' => $locationId,
            'location' => $price->location ? [
                'id' => $price->location->id,
                'name' => $price->location->name,
            ] : null,
            'strategy' => [
                'value' => $price->strategy->value,
                'label' => $price->strategy->label(),
            ],
            'calculation' => [
                'base_price' => $price->price,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'breakdown' => $breakdown,
            ],
            'date' => $date,
            'calculated_at' => now()->toISOString(),
        ];

        activity()
            ->performedOn($price)
            ->causedBy(auth()->user())
            ->withProperties([
                'product_id' => $productId,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
            ])
            ->log('Calculated product price');

        return new PriceCalculationResource($result);
    }

    /**
     * Generate detailed price breakdown based on strategy
     */
    protected function generateBreakdown(
        string $strategy,
        string $basePrice,
        string $unitPrice,
        string $totalPrice,
        string $quantity,
        array $context
    ): array {
        $breakdown = [
            'base_price' => $basePrice,
            'quantity' => $quantity,
        ];

        switch ($strategy) {
            case 'percentage':
                $percentage = $context['percentage'] ?? '0';
                $adjustment = MathHelper::percentage($basePrice, $percentage);
                $breakdown['percentage'] = $percentage;
                $breakdown['adjustment'] = $adjustment;
                $breakdown['adjusted_price'] = $unitPrice;
                break;

            case 'tiered':
                $tiers = $context['tiers'] ?? [];
                $applicableTier = null;

                foreach ($tiers as $tier) {
                    if (MathHelper::compare($quantity, $tier['min_quantity']) >= 0) {
                        $applicableTier = $tier;
                    }
                }

                if ($applicableTier) {
                    $breakdown['applied_tier'] = $applicableTier;
                    $breakdown['tier_price'] = $applicableTier['price'];
                }
                break;

            case 'volume':
                $thresholds = $context['thresholds'] ?? [];
                $applicableThreshold = null;

                foreach ($thresholds as $threshold) {
                    if (MathHelper::compare($quantity, $threshold['min_quantity']) >= 0) {
                        $applicableThreshold = $threshold;
                    }
                }

                if ($applicableThreshold) {
                    $breakdown['applied_threshold'] = $applicableThreshold;

                    if (isset($applicableThreshold['discount_percentage'])) {
                        $discount = MathHelper::percentage($basePrice, $applicableThreshold['discount_percentage']);
                        $breakdown['discount_percentage'] = $applicableThreshold['discount_percentage'];
                        $breakdown['discount_amount'] = $discount;
                    }
                }
                break;

            case 'time_based':
                $breakdown['date'] = $context['date'] ?? now()->toISOString();

                if (isset($context['applied_rule'])) {
                    $breakdown['applied_rule'] = $context['applied_rule'];
                }
                break;

            case 'rule_based':
                if (isset($context['applied_rules'])) {
                    $breakdown['applied_rules'] = $context['applied_rules'];
                }
                break;
        }

        $breakdown['final_unit_price'] = $unitPrice;
        $breakdown['final_total_price'] = $totalPrice;

        return $breakdown;
    }
}
