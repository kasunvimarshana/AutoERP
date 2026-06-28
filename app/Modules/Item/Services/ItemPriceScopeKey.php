<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Item\Enums\ItemPriceType;

final class ItemPriceScopeKey
{
    private const GLOBAL_ORGANIZATION_SCOPE = 'global';
    private const ALL_VARIANTS_SCOPE = 'all_variants';

    public static function for(
        ?int $organizationUnitId,
        ?int $itemVariantId,
        ItemPriceType $priceType,
        int $currencyId,
        int $uomId,
    ): string {
        return hash('sha256', implode('|', [
            $organizationUnitId === null ? self::GLOBAL_ORGANIZATION_SCOPE : (string) $organizationUnitId,
            $itemVariantId === null ? self::ALL_VARIANTS_SCOPE : (string) $itemVariantId,
            $priceType->value,
            (string) $currencyId,
            (string) $uomId,
        ]));
    }
}
