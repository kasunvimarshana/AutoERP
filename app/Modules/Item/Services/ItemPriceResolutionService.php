<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\ItemPriceResolutionResult;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemPrice;
use Modules\Item\Models\ItemUnit;
use Modules\Tenant\Models\TenantModel;
use Modules\UOM\Models\UnitOfMeasureModel;

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
        $date ??= CarbonImmutable::now()->toDateString();
        $priceTypes = $this->priceTypesForContext($context);

        foreach ($priceTypes as $priceType) {
            $price = $this->contextualPrice(
                $item,
                $priceType,
                $uomId,
                $organizationUnitId,
                $currencyId,
                $date,
                $variantId,
            );

            if ($price instanceof ItemPrice) {
                return new ItemPriceResolutionResult(
                    amount: $this->math->normalize((string) $price->amount),
                    currencyId: $price->currency_id === null ? null : (int) $price->currency_id,
                    uomId: $price->uom_id === null ? null : (int) $price->uom_id,
                    source: 'specific_price',
                    priceType: $priceType->value,
                    priceId: (int) $price->getKey(),
                    metadata: [
                        'context' => $context,
                        'basis' => $price->uom_id === null ? 'price_record_unspecified_uom' : 'price_record_uom',
                    ],
                );
            }
        }

        if ($this->canUseStandardPrice($context)) {
            return $this->standardPrice($item, $context, $uomId, $organizationUnitId, $currencyId);
        }

        return new ItemPriceResolutionResult(
            amount: null,
            currencyId: $currencyId,
            uomId: $uomId,
            source: 'manual',
            priceType: $context,
            metadata: ['context' => $context],
        );
    }

    /**
     * @return list<ItemPriceType>
     */
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

    private function canUseStandardPrice(string $context): bool
    {
        return in_array($context, [
            self::CONTEXT_SALES,
            self::CONTEXT_SERVICE,
            self::CONTEXT_INVOICE,
        ], true);
    }

    private function contextualPrice(
        Item $item,
        ItemPriceType $priceType,
        ?int $uomId,
        ?int $organizationUnitId,
        ?int $currencyId,
        string $date,
        ?int $variantId,
    ): ?ItemPrice {
        return ItemPrice::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('price_type', $priceType->value)
            ->where('is_active', true)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId)),
            )
            ->when(
                $currencyId === null,
                fn (Builder $query) => $query->whereNull('currency_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('currency_id')
                    ->orWhere('currency_id', $currencyId)),
            )
            ->when(
                $uomId === null,
                fn (Builder $query) => $query->whereNull('uom_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('uom_id')
                    ->orWhere('uom_id', $uomId)),
            )
            ->when(
                $variantId === null,
                fn (Builder $query) => $query->whereNull('item_variant_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('item_variant_id')
                    ->orWhere('item_variant_id', $variantId)),
            )
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->when($organizationUnitId !== null, fn (Builder $query) => $query->orderByRaw('case when organization_unit_id = ? then 0 else 1 end', [$organizationUnitId]))
            ->when($variantId !== null, fn (Builder $query) => $query->orderByRaw('case when item_variant_id = ? then 0 else 1 end', [$variantId]))
            ->when($currencyId !== null, fn (Builder $query) => $query->orderByRaw('case when currency_id = ? then 0 else 1 end', [$currencyId]))
            ->when($uomId !== null, fn (Builder $query) => $query->orderByRaw('case when uom_id = ? then 0 else 1 end', [$uomId]))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    private function standardPrice(
        Item $item,
        string $context,
        ?int $uomId,
        ?int $organizationUnitId,
        ?int $currencyId,
    ): ItemPriceResolutionResult {
        if ($item->standard_price === null) {
            return new ItemPriceResolutionResult(
                amount: null,
                currencyId: $currencyId,
                uomId: $uomId,
                source: 'manual',
                priceType: $context,
                metadata: ['context' => $context],
            );
        }

        if ($item->base_uom_id === null) {
            throw new InvalidArgumentException('Standard Price requires an item Base UOM.');
        }

        $tenant = TenantModel::query()->find((int) $item->tenant_id);
        $tenantCurrencyId = $tenant?->currency_id === null ? null : (int) $tenant->currency_id;
        if ($currencyId !== null && $tenantCurrencyId !== $currencyId) {
            throw new InvalidArgumentException('Standard Price is only valid in the tenant base currency; configure a contextual or manual price for the selected currency.');
        }

        $selectedUomId = $uomId ?? (int) $item->base_uom_id;
        $factor = $this->standardPriceFactor($item, $selectedUomId, $organizationUnitId);

        return new ItemPriceResolutionResult(
            amount: $this->math->mul((string) $item->standard_price, $factor),
            currencyId: $tenantCurrencyId,
            uomId: $selectedUomId,
            source: 'standard_price',
            priceType: $context,
            metadata: [
                'context' => $context,
                'basis' => 'per_base_uom_tax_exclusive_tenant_base_currency',
                'base_uom_id' => (int) $item->base_uom_id,
                'conversion_factor' => $factor,
            ],
        );
    }

    private function standardPriceFactor(Item $item, int $selectedUomId, ?int $organizationUnitId): string
    {
        $baseUomId = (int) $item->base_uom_id;
        $unit = ItemUnit::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('uom_id', $selectedUomId)
            ->where('is_active', true)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId)),
            )
            ->orderByRaw('case when unit_role = ? then 0 else 1 end', ['base'])
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (! $unit instanceof ItemUnit) {
            throw new InvalidArgumentException('Standard Price fallback requires the selected UOM to be an active item unit.');
        }

        $this->assertUomCompatible((int) $item->tenant_id, $organizationUnitId, $baseUomId, $selectedUomId);

        return $selectedUomId === $baseUomId
            ? '1.000000'
            : $this->math->normalize((string) $unit->conversion_factor);
    }

    private function assertUomCompatible(int $tenantId, ?int $organizationUnitId, int $baseUomId, int $selectedUomId): void
    {
        $base = UnitOfMeasureModel::query()->findOrFail($baseUomId);
        $selected = UnitOfMeasureModel::query()->findOrFail($selectedUomId);

        foreach ([$base, $selected] as $uom) {
            if ((int) $uom->tenant_id !== $tenantId) {
                throw new InvalidArgumentException('Standard Price UOM belongs to a different tenant.');
            }
            if ($uom->organization_unit_id !== null && $organizationUnitId === null) {
                throw new InvalidArgumentException('Global Standard Price fallback may only use global UOM records.');
            }
            if ($uom->organization_unit_id !== null && (int) $uom->organization_unit_id !== $organizationUnitId) {
                throw new InvalidArgumentException('Standard Price UOM belongs to a different organization unit.');
            }
            if (! (bool) $uom->is_active) {
                throw new InvalidArgumentException('Standard Price UOM must be active.');
            }
        }

        if ($base->type !== $selected->type || $base->category !== $selected->category) {
            throw new InvalidArgumentException('Standard Price UOM must be compatible with the item Base UOM.');
        }
    }
}
