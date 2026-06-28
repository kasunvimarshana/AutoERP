<?php

declare(strict_types=1);

namespace Modules\Item\Services\Tax;

use InvalidArgumentException;
use Modules\Item\Models\Item;
use Modules\Tax\Contracts\TaxItemContextProviderInterface;
use Modules\Tax\Data\TaxItemContext;

final class ItemTaxContextProvider implements TaxItemContextProviderInterface
{
    public function find(int $tenantId, ?int $organizationUnitId, int $itemId): ?TaxItemContext
    {
        $item = Item::query()->find($itemId);
        if (! $item instanceof Item) {
            return null;
        }

        if ((int) $item->tenant_id !== $tenantId
            || ($organizationUnitId !== null
                && $item->organization_unit_id !== null
                && (int) $item->organization_unit_id !== $organizationUnitId)) {
            throw new InvalidArgumentException('Item belongs to a different tax scope.');
        }

        return new TaxItemContext(
            itemId: (int) $item->getKey(),
            isTaxExempt: (bool) ($item->is_tax_exempt ?? false),
            defaultTaxGroupId: $item->default_tax_group_id === null ? null : (int) $item->default_tax_group_id,
            purchaseTaxGroupId: $item->purchase_tax_group_id === null ? null : (int) $item->purchase_tax_group_id,
            salesTaxGroupId: $item->sales_tax_group_id === null ? null : (int) $item->sales_tax_group_id,
        );
    }
}
