<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use DateTimeInterface;
use Modules\Configuration\Application\Repositories\CurrencyRepositoryInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Pricing\Application\Contracts\Services\DiscountServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceResolverServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceValidationServiceInterface;
use Modules\Pricing\Application\Contracts\Services\TierPricingServiceInterface;
use Modules\Pricing\Application\Repositories\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\DiscountRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingRuleConditionRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingRuleRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingTierRepositoryInterface;
use Modules\Pricing\Application\Repositories\SupplierPriceListRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Throwable;

final class PriceResolverService implements PriceResolverServiceInterface
{
    public function __construct(
        private readonly PriceValidationServiceInterface $validationService,
        private readonly PriceListRepositoryInterface $priceListRepository,
        private readonly PriceListItemRepositoryInterface $priceListItemRepository,
        private readonly PricingRuleRepositoryInterface $pricingRuleRepository,
        private readonly PricingRuleConditionRepositoryInterface $pricingRuleConditionRepository,
        private readonly PricingTierRepositoryInterface $pricingTierRepository,
        private readonly DiscountRepositoryInterface $discountRepository,
        private readonly DiscountServiceInterface $discountService,
        private readonly TierPricingServiceInterface $tierPricingService,
        private readonly UomConversionRepositoryInterface $uomConversionRepository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly CustomerPriceListRepositoryInterface $customerPriceListRepository,
        private readonly SupplierPriceListRepositoryInterface $supplierPriceListRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,
    ) {
    }

    public function resolvePrice(array $context): Result
    {
        try {
            $validation = $this->validationService->validateResolveContext($context);
            if ($validation->isFailure()) {
                return $validation;
            }

            $tenantId = (int) $context['tenant_id'];
            $itemId = (int) $context['item_id'];
            $quantity = (float) $context['quantity'];
            $requestedUomId = isset($context['uom_id']) ? (int) $context['uom_id'] : null;
            $currencyId = isset($context['currency_id']) ? (int) $context['currency_id'] : null;
            $priceListId = isset($context['price_list_id']) ? (int) $context['price_list_id'] : null;
            $partyType = isset($context['party_type']) ? strtolower((string) $context['party_type']) : null;
            $partyId = isset($context['party_id']) ? (int) $context['party_id'] : null;
            $sourceType = isset($context['source_type']) ? (string) $context['source_type'] : null;
            $sourceId = isset($context['source_id']) ? (int) $context['source_id'] : null;
            $resolveDate = $this->normalizeDate($context['date'] ?? null) ?? now()->toDateString();

            $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
            if (! $item instanceof DataRecord) {
                return Result::failure(new Error(PricingErrorCode::NOT_FOUND, 'Item not found in tenant.'));
            }

            $candidateListIds = $this->resolveCandidatePriceListIds($tenantId, $priceListId, $partyType, $partyId, $sourceType, $sourceId, $resolveDate);
            if ($candidateListIds === []) {
                return Result::failure(new Error(PricingErrorCode::NOT_FOUND, 'No applicable price list found.'));
            }

            $candidateItems = $this->priceListItemRepository->list([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'is_active' => true,
            ]);

            $selected = $this->selectBestPriceItem(
                $candidateItems,
                $candidateListIds,
                $tenantId,
                $partyType,
                $partyId,
                $sourceType,
                $sourceId,
                $currencyId,
                $requestedUomId,
                $quantity,
                $resolveDate,
            );

            if ($selected === null) {
                return Result::failure(new Error(PricingErrorCode::NOT_FOUND, 'No applicable price list item found.'));
            }

            $effectiveQuantity = $selected['effective_quantity'];
            $baseUnitPrice = (float) $selected['item']->get('price', 0);
            $tierBreakdown = $this->resolveTierBreakdown((int) $selected['item']->id(), $effectiveQuantity);
            if ($tierBreakdown->isFailure()) {
                return $tierBreakdown;
            }

            $tierInfo = $tierBreakdown->valueOrFail();
            if (is_array($tierInfo) && ($tierInfo['tier_price'] ?? null) !== null) {
                $baseUnitPrice = (float) $tierInfo['tier_price'];
            } elseif (is_array($tierInfo) && isset($tierInfo['tier_adjustment']) && isset($tierInfo['applied_tier'])) {
                $appliedTier = $tierInfo['applied_tier'];
                $adjustmentType = strtolower((string) ($appliedTier['adjustment_type'] ?? 'override'));
                $adjustmentValue = (float) ($appliedTier['adjustment_value'] ?? 0);
                if ($adjustmentType === 'percentage') {
                    $baseUnitPrice = round($baseUnitPrice + ($baseUnitPrice * ($adjustmentValue / 100)), 4);
                } elseif ($adjustmentType === 'fixed') {
                    $baseUnitPrice = round($baseUnitPrice + $adjustmentValue, 4);
                } elseif ($adjustmentType === 'override') {
                    $baseUnitPrice = $adjustmentValue;
                }
            }

            $baseAmount = round($baseUnitPrice * $effectiveQuantity, 4);
            $discountBreakdown = $this->resolveDiscountBreakdown($tenantId, $itemId, $partyType, $partyId, $sourceType, $sourceId, $currencyId, $resolveDate, $effectiveQuantity, $baseAmount);
            if ($discountBreakdown->isFailure()) {
                return $discountBreakdown;
            }

            $discountInfo = $discountBreakdown->valueOrFail();
            $discountAmount = (float) ($discountInfo['discount_amount'] ?? 0);
            $finalAmount = max(0, round($baseAmount - $discountAmount, 4));

            return Result::success(new DataRecord([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'resolved_quantity' => $effectiveQuantity,
                'requested_uom_id' => $requestedUomId,
                'uom_id' => $selected['item']->get('uom_id'),
                'currency_id' => $selected['currency_id'] ?? $currencyId,
                'price_list_id' => (int) $selected['price_list']->id(),
                'price_list_item_id' => (int) $selected['item']->id(),
                'base_unit_price' => $baseUnitPrice,
                'base_amount' => $baseAmount,
                'discount_amount' => $discountAmount,
                'discount_percentage' => (float) ($discountInfo['discount_percentage'] ?? 0),
                'final_amount' => $finalAmount,
                'tax_inclusive' => (bool) $selected['item']->get('is_tax_inclusive', false),
                'applied_price_list' => $selected['price_list']->toArray(),
                'applied_price_item' => $selected['item']->toArray(),
                'applied_tier' => $tierInfo['applied_tier'] ?? null,
                'applied_discounts' => $discountInfo['applied_discounts'] ?? [],
                'explanation' => [
                    'candidate_price_list_ids' => $candidateListIds,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'party_type' => $partyType,
                    'party_id' => $partyId,
                    'date' => $resolveDate,
                ],
            ]));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @return list<int>
     */
    private function resolveCandidatePriceListIds(int $tenantId, ?int $explicitPriceListId, ?string $partyType, ?int $partyId, ?string $sourceType, ?int $sourceId, string $resolveDate): array
    {
        $ids = [];

        if ($explicitPriceListId !== null) {
            $ids[] = $explicitPriceListId;
        }

        $genericLists = $this->priceListRepository->list(['tenant_id' => $tenantId, 'is_active' => true]);
        foreach ($genericLists as $priceList) {
            if (! $priceList instanceof DataRecord) {
                continue;
            }

            if (! $this->isDateApplicable($resolveDate, $priceList->get('valid_from'), $priceList->get('valid_to'))) {
                continue;
            }

            $scopeType = strtolower((string) $priceList->get('scope_type', 'generic'));
            $listSourceType = $priceList->get('source_type');
            $listSourceId = $priceList->get('source_id');
            if ($scopeType === 'generic') {
                $ids[] = (int) $priceList->id();
            }

            if ($partyType !== null && $partyId !== null && $scopeType === $partyType) {
                $ids[] = (int) $priceList->id();
            }

            if ($sourceType !== null && $sourceId !== null && $listSourceType === $sourceType && (int) $listSourceId === $sourceId) {
                $ids[] = (int) $priceList->id();
            }
        }

        if ($partyType === 'customer' && $partyId !== null) {
            foreach ($this->customerPriceListRepository->list(['tenant_id' => $tenantId, 'customer_id' => $partyId, 'is_active' => true]) as $map) {
                if ($map instanceof DataRecord && $this->isDateApplicable($resolveDate, $map->get('valid_from'), $map->get('valid_to'))) {
                    $ids[] = (int) $map->get('price_list_id');
                }
            }
        }

        if ($partyType === 'supplier' && $partyId !== null) {
            foreach ($this->supplierPriceListRepository->list(['tenant_id' => $tenantId, 'supplier_id' => $partyId, 'is_active' => true]) as $map) {
                if ($map instanceof DataRecord && $this->isDateApplicable($resolveDate, $map->get('valid_from'), $map->get('valid_to'))) {
                    $ids[] = (int) $map->get('price_list_id');
                }
            }
        }

        $ids = array_values(array_unique(array_filter($ids, static fn ($value): bool => (int) $value > 0)));

        return $ids;
    }

    /**
     * @param list<DataRecord> $candidateItems
     * @return array<string, mixed>|null
     */
    private function selectBestPriceItem(
        array $candidateItems,
        array $candidateListIds,
        int $tenantId,
        ?string $partyType,
        ?int $partyId,
        ?string $sourceType,
        ?int $sourceId,
        ?int $currencyId,
        ?int $requestedUomId,
        float $quantity,
        string $resolveDate,
    ): ?array {
        $best = null;

        foreach ($candidateItems as $item) {
            if (! $item instanceof DataRecord) {
                continue;
            }

            if (! in_array((int) $item->get('price_list_id'), $candidateListIds, true)) {
                continue;
            }

            if (! $this->isDateApplicable($resolveDate, $item->get('valid_from'), $item->get('valid_to'))) {
                continue;
            }

            if ($currencyId !== null && $item->get('currency_id') !== null && (int) $item->get('currency_id') !== $currencyId) {
                continue;
            }

            $effectiveQuantity = $this->normalizeQuantity($tenantId, $item, $requestedUomId, $quantity);
            if ($effectiveQuantity === null) {
                continue;
            }

            $minQuantity = (float) $item->get('min_quantity', 1);
            $maxQuantity = $item->get('max_quantity') !== null ? (float) $item->get('max_quantity') : null;
            if ($effectiveQuantity < $minQuantity || ($maxQuantity !== null && $effectiveQuantity > $maxQuantity)) {
                continue;
            }

            $score = (int) $item->get('priority', 0);
            if ($requestedUomId !== null && (int) $item->get('uom_id') === $requestedUomId) {
                $score += 20;
            }

            if ($currencyId !== null && (int) $item->get('currency_id', 0) === $currencyId) {
                $score += 10;
            }

            if ($partyType !== null && $partyId !== null && strtolower((string) $item->get('party_type', '')) === $partyType && (int) $item->get('party_id', 0) === $partyId) {
                $score += 100;
            }

            if ($sourceType !== null && $sourceId !== null && (string) $item->get('source_type', '') === $sourceType && (int) $item->get('source_id', 0) === $sourceId) {
                $score += 100;
            }

            if ($best === null || $score > (int) $best['score']) {
                $best = [
                    'score' => $score,
                    'price_list' => $this->priceListRepository->findById((int) $item->get('price_list_id')),
                    'item' => $item,
                    'currency_id' => $item->get('currency_id'),
                    'effective_quantity' => $effectiveQuantity,
                ];
            }
        }

        if ($best === null || ! $best['price_list'] instanceof DataRecord) {
            return null;
        }

        return $best;
    }

    private function normalizeQuantity(int $tenantId, DataRecord $item, ?int $requestedUomId, float $quantity): ?float
    {
        if ($requestedUomId === null || (int) $item->get('uom_id') === $requestedUomId) {
            return $quantity;
        }

        $conversion = $this->uomConversionRepository->findConversionBetween($requestedUomId, $item->get('uom_id'), $tenantId, (int) $item->get('item_id'));
        if (! $conversion instanceof DataRecord) {
            return null;
        }

        return round($quantity * (float) $conversion->get('factor', 1), 4);
    }

    private function resolveTierBreakdown(int $priceListItemId, float $quantity): Result
    {
        $tiers = [];
        foreach ($this->pricingTierRepository->list(['price_list_item_id' => $priceListItemId, 'is_active' => true]) as $tier) {
            if ($tier instanceof DataRecord) {
                $tiers[] = $tier->toArray();
            }
        }

        return $this->tierPricingService->resolveTier($tiers, $quantity);
    }

    private function resolveDiscountBreakdown(
        int $tenantId,
        int $itemId,
        ?string $partyType,
        ?int $partyId,
        ?string $sourceType,
        ?int $sourceId,
        ?int $currencyId,
        string $resolveDate,
        float $quantity,
        float $baseAmount,
    ): Result {
        $discounts = [];
        foreach ($this->discountRepository->list(['tenant_id' => $tenantId, 'is_active' => true]) as $discount) {
            if (! $discount instanceof DataRecord) {
                continue;
            }

            if (! $this->isDateApplicable($resolveDate, $discount->get('valid_from'), $discount->get('valid_to'))) {
                continue;
            }

            if ($discount->get('item_id') !== null && (int) $discount->get('item_id') !== $itemId) {
                continue;
            }

            if ($currencyId !== null && $discount->get('currency_id') !== null && (int) $discount->get('currency_id') !== $currencyId) {
                continue;
            }

            if ($partyType !== null && $partyId !== null && $discount->get('customer_id') !== null && $partyType === 'customer' && (int) $discount->get('customer_id') !== $partyId) {
                continue;
            }

            if ($partyType !== null && $partyId !== null && $discount->get('supplier_id') !== null && $partyType === 'supplier' && (int) $discount->get('supplier_id') !== $partyId) {
                continue;
            }

            if ($sourceType !== null && $sourceId !== null && $discount->get('source_type') !== null && (string) $discount->get('source_type') !== $sourceType) {
                continue;
            }

            $discounts[] = $discount->toArray();
        }

        return $this->discountService->resolveDiscounts($discounts, $baseAmount, $quantity);
    }

    private function isDateApplicable(string $date, mixed $validFrom, mixed $validTo): bool
    {
        if ($validFrom !== null && strtotime($date) < strtotime((string) $validFrom)) {
            return false;
        }

        if ($validTo !== null && strtotime($date) > strtotime((string) $validTo)) {
            return false;
        }

        return true;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return date('Y-m-d', strtotime((string) $value));
    }
}
