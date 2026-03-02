<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Helpers\DecimalHelper;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Pricing\Application\DTOs\CreateProductPriceDTO;
use Modules\Pricing\Application\DTOs\PriceCalculationDTO;
use Modules\Pricing\Domain\Contracts\PricingRepositoryContract;
use Modules\Pricing\Domain\Entities\DiscountRule;
use Modules\Pricing\Domain\Entities\ProductPrice;

/**
 * Pricing service.
 *
 * Orchestrates all pricing use cases: price calculation, discount resolution,
 * and price list management.
 *
 * ALL calculations use DecimalHelper (BCMath) — no float arithmetic allowed.
 */
class PricingService implements ServiceContract
{
    public function __construct(
        private readonly PricingRepositoryContract $pricingRepository,
    ) {}

    /**
     * Calculate the final price for a product given quantity and context.
     *
     * Resolves the applicable unit price, then finds and applies the best
     * matching discount rule. All arithmetic uses BCMath exclusively.
     *
     * @return array{unit_price: string, discount: string, final_price: string, currency: string}
     */
    public function calculatePrice(PriceCalculationDTO $dto): array
    {
        $unitPrice = $this->resolveUnitPrice($dto);
        $currency  = $this->resolveCurrency($dto);
        $discount  = $this->resolveDiscount($dto, $unitPrice);

        $lineTotal  = DecimalHelper::mul($unitPrice, $dto->quantity, DecimalHelper::SCALE_INTERMEDIATE);
        $finalPrice = DecimalHelper::sub($lineTotal, $discount, DecimalHelper::SCALE_INTERMEDIATE);

        return [
            'unit_price'  => DecimalHelper::round($unitPrice, DecimalHelper::SCALE_STANDARD),
            'discount'    => DecimalHelper::round($discount, DecimalHelper::SCALE_STANDARD),
            'final_price' => DecimalHelper::toMonetary($finalPrice),
            'currency'    => $currency,
        ];
    }

    /**
     * Return all price lists for the current tenant.
     */
    public function listPriceLists(): Collection
    {
        return $this->pricingRepository->all();
    }

    /**
     * Create a new price list.
     *
     * @param array<string, mixed> $data
     */
    public function createPriceList(array $data): Model
    {
        return DB::transaction(fn () => $this->pricingRepository->create($data));
    }

    /**
     * Show a single price list by ID.
     */
    public function showPriceList(int|string $id): Model
    {
        return $this->pricingRepository->findOrFail($id);
    }

    /**
     * Update an existing price list.
     *
     * @param array<string, mixed> $data
     */
    public function updatePriceList(int|string $id, array $data): Model
    {
        return DB::transaction(fn () => $this->pricingRepository->update($id, $data));
    }

    /**
     * Delete a price list.
     */
    public function deletePriceList(int|string $id): bool
    {
        return DB::transaction(fn () => $this->pricingRepository->delete($id));
    }

    /**
     * List all discount rules.
     */
    public function listDiscountRules(): Collection
    {
        return $this->pricingRepository->allDiscountRules();
    }

    /**
     * Create a new discount rule.
     *
     * @param array<string, mixed> $data
     */
    public function createDiscountRule(array $data): Model
    {
        return DB::transaction(fn () => $this->pricingRepository->createDiscountRule($data));
    }

    /**
     * Show a single discount rule.
     */
    public function showDiscountRule(int|string $id): Model
    {
        return $this->pricingRepository->findDiscountRuleOrFail($id);
    }

    /**
     * Update an existing discount rule.
     *
     * @param array<string, mixed> $data
     */
    public function updateDiscountRule(int|string $id, array $data): Model
    {
        return DB::transaction(fn () => $this->pricingRepository->updateDiscountRule($id, $data));
    }

    /**
     * Delete a discount rule.
     */
    public function deleteDiscountRule(int|string $id): bool
    {
        return DB::transaction(fn () => $this->pricingRepository->deleteDiscountRule($id));
    }

    /**
     * List product prices for a given product.
     *
     * Returns all ProductPrice records scoped to the current tenant
     * and filtered by the given product ID.
     *
     * @return Collection<int, ProductPrice>
     */
    public function listProductPrices(int $productId): Collection
    {
        return ProductPrice::query()
            ->where('product_id', $productId)
            ->with('priceList')
            ->get();
    }

    /**
     * Create a new product price entry.
     *
     * Monetary values are rounded to SCALE_STANDARD (4 decimal places) using BCMath
     * before persistence. All mutations are wrapped in a database transaction.
     */
    public function createProductPrice(CreateProductPriceDTO $dto): ProductPrice
    {
        return DB::transaction(function () use ($dto): ProductPrice {
            /** @var ProductPrice $price */
            $price = ProductPrice::create([
                'product_id'    => $dto->productId,
                'price_list_id' => $dto->priceListId,
                'uom_id'        => $dto->uomId,
                'selling_price' => DecimalHelper::round($dto->sellingPrice, DecimalHelper::SCALE_STANDARD),
                'cost_price'    => $dto->costPrice !== null
                    ? DecimalHelper::round($dto->costPrice, DecimalHelper::SCALE_STANDARD)
                    : null,
                'min_quantity'  => $dto->minQuantity !== null
                    ? DecimalHelper::round($dto->minQuantity, DecimalHelper::SCALE_STANDARD)
                    : null,
                'valid_from'    => $dto->validFrom,
                'valid_to'      => $dto->validTo,
            ]);

            return $price;
        });
    }

    /**
     * Resolve the unit selling price for the given product/context.
     *
     * Looks up the default active price list for the tenant, then finds the
     * most specific matching ProductPrice record. Falls back to '0.0000'.
     */
    private function resolveUnitPrice(PriceCalculationDTO $dto): string
    {
        $priceRecord = ProductPrice::query()
            ->where('product_id', $dto->productId)
            ->when($dto->uomId !== null, fn ($q) => $q->where('uom_id', $dto->uomId))
            ->where(function ($q) use ($dto): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $dto->date);
            })
            ->where(function ($q) use ($dto): void {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $dto->date);
            })
            ->where(function ($q) use ($dto): void {
                $q->whereNull('min_quantity')
                    ->orWhereRaw('CAST(min_quantity AS DECIMAL(20,4)) <= CAST(? AS DECIMAL(20,4))', [$dto->quantity]);
            })
            ->orderByRaw('CAST(min_quantity AS DECIMAL(20,4)) DESC')
            ->first();

        if ($priceRecord === null) {
            return '0.0000';
        }

        return (string) $priceRecord->selling_price;
    }

    /**
     * Resolve the ISO currency code for the applicable price list.
     */
    private function resolveCurrency(PriceCalculationDTO $dto): string
    {
        $priceRecord = ProductPrice::query()
            ->where('product_id', $dto->productId)
            ->with('priceList')
            ->first();

        return $priceRecord?->priceList?->currency_code ?? 'USD';
    }

    /**
     * Resolve the total discount amount for the given context.
     *
     * Finds the most specific active matching DiscountRule and computes
     * the discount amount using BCMath. Returns '0.0000' when no rule matches.
     *
     * Discount types:
     *   - percentage: discount = (discount_value / 100) * unit_price * quantity
     *   - flat:        discount = discount_value (fixed reduction)
     */
    private function resolveDiscount(PriceCalculationDTO $dto, string $unitPrice): string
    {
        $rule = DiscountRule::query()
            ->where('is_active', true)
            ->where(function ($q) use ($dto): void {
                $q->where('apply_to', 'all')
                    ->orWhere(function ($q2) use ($dto): void {
                        $q2->where('apply_to', 'product')->where('product_id', $dto->productId);
                    });
            })
            ->when($dto->customerTier !== null, function ($q) use ($dto): void {
                $q->where(function ($q2) use ($dto): void {
                    $q2->whereNull('customer_tier')->orWhere('customer_tier', $dto->customerTier);
                });
            })
            ->when($dto->locationId !== null, function ($q) use ($dto): void {
                $q->where(function ($q2) use ($dto): void {
                    $q2->whereNull('location_id')->orWhere('location_id', $dto->locationId);
                });
            })
            ->where(function ($q) use ($dto): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $dto->date);
            })
            ->where(function ($q) use ($dto): void {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $dto->date);
            })
            ->where(function ($q) use ($dto): void {
                $q->whereNull('min_quantity')
                    ->orWhereRaw('CAST(min_quantity AS DECIMAL(20,4)) <= CAST(? AS DECIMAL(20,4))', [$dto->quantity]);
            })
            ->first();

        if ($rule === null) {
            return '0.0000';
        }

        $discountValue = (string) $rule->discount_value;

        if ($rule->discount_type === 'percentage') {
            // discount = (discount_value / 100) * unit_price * quantity
            $rate          = DecimalHelper::div($discountValue, '100', DecimalHelper::SCALE_INTERMEDIATE);
            $lineTotal     = DecimalHelper::mul($unitPrice, $dto->quantity, DecimalHelper::SCALE_INTERMEDIATE);
            return DecimalHelper::mul($rate, $lineTotal, DecimalHelper::SCALE_INTERMEDIATE);
        }

        // flat: fixed discount amount
        return DecimalHelper::round($discountValue, DecimalHelper::SCALE_INTERMEDIATE);
    }
}
