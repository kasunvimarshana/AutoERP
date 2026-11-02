<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\ItemPriceResolutionResult;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemPrice;
use Modules\Tenant\Models\TenantModel;

final class ItemPriceResolutionService
{
    public const CONTEXT_SALES = 'sales';
    public const CONTEXT_SERVICE = 'service';
    public const CONTEXT_INVOICE = 'invoice';
    public const CONTEXT_PURCHASE = 'purchase';
    public const CONTEXT_RENTAL = 'rental';

    public function __construct(private readonly DecimalMath $math) {}

    public function resolvePrice(
        Item $item,
        string $context,
        ?int $uomId = null,
        ?int $organizationUnitId = null,
        ?int $currencyId = null,
        ?string $date = null,
        ?int $variantId = null,
    ): ItemPriceResolutionResult {
        $effectiveDate = $date ?? CarbonImmutable::now()->toDateString();
        $resolvedUomId = $uomId ?? ($item->base_uom_id === null ? null : (int) $item->base_uom_id);
        $resolvedCurrencyId = $currencyId ?? $this->tenantBaseCurrencyId((int) $item->tenant_id);

        if ($resolvedUomId !== null && $resolvedCurrencyId !== null) {
            foreach ($this->priceTypesForContext($context) as $priceType) {
                $price = $this->contextualPrice(
                    item: $item,
                    priceType: $priceType,
                    uomId: $resolvedUomId,
                    organizationUnitId: $organizationUnitId,
                    currencyId: $resolvedCurrencyId,
                    date: $effectiveDate,
                    variantId: $variantId,
                );

                if ($price instanceof ItemPrice) {
                    return new ItemPriceResolutionResult(
                        amount: $this->math->normalize((string) $price->amount),
                        currencyId: (int) $price->currency_id,
                        uomId: (int) $price->uom_id,
                        source: 'item_price_revision',
                        priceType: $priceType->value,
                        priceId: (int) $price->getKey(),
                        metadata: [
                            'context' => $context,
                            'effective_date' => $effectiveDate,
                            'revision_no' => (int) $price->revision_no,
                            'lineage_key' => (string) $price->lineage_key,
                        ],
                    );
                }
            }
        }

        return new ItemPriceResolutionResult(
            amount: null,
            currencyId: $resolvedCurrencyId,
            uomId: $resolvedUomId,
            source: 'manual',
            priceType: $context,
            metadata: ['context' => $context, 'effective_date' => $effectiveDate],
        );
    }

    /** @return list<ItemPriceType> */
    private function priceTypesForContext(string $context): array
    {
        return match ($context) {
            self::CONTEXT_SALES => [ItemPriceType::Sales],
            self::CONTEXT_SERVICE => [ItemPriceType::Service, ItemPriceType::Sales],
            self::CONTEXT_INVOICE => [ItemPriceType::Sales, ItemPriceType::Service],
            self::CONTEXT_PURCHASE => [ItemPriceType::Purchase],
            self::CONTEXT_RENTAL => [ItemPriceType::Rental],
            default => [],
        };
    }

    private function contextualPrice(
        Item $item,
        ItemPriceType $priceType,
        int $uomId,
        ?int $organizationUnitId,
        int $currencyId,
        string $date,
        ?int $variantId,
    ): ?ItemPrice {
        return ItemPrice::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('price_type', $priceType->value)
            ->where('currency_id', $currencyId)
            ->where('uom_id', $uomId)
            ->whereNull('recorded_to')
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId)),
            )
            ->when(
                $variantId === null,
                fn (Builder $query) => $query->whereNull('item_variant_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('item_variant_id')
                    ->orWhere('item_variant_id', $variantId)),
            )
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->when($organizationUnitId !== null, fn (Builder $query) => $query->orderByRaw('case when organization_unit_id = ? then 0 else 1 end', [$organizationUnitId]))
            ->when($variantId !== null, fn (Builder $query) => $query->orderByRaw('case when item_variant_id = ? then 0 else 1 end', [$variantId]))
            ->orderByDesc('effective_from')
            ->orderByDesc('recorded_from')
            ->orderByDesc('id')
            ->first();
    }

    private function tenantBaseCurrencyId(int $tenantId): ?int
    {
        $currencyId = TenantModel::query()->whereKey($tenantId)->value('base_currency_id');

        return $currencyId === null ? null : (int) $currencyId;
    }
}
