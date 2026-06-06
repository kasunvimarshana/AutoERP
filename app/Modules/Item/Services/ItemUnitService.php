<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\ItemUnitData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Validators\ItemValidationService;

final class ItemUnitService
{
    public function __construct(
        private readonly ItemValidationService $validator,
        private readonly DecimalMath $math,
    ) {}

    public function assign(Item $item, ItemUnitData $data): ItemUnit
    {
        $this->validator->validateUnit($item, $data);

        if ($data->isDefault) {
            ItemUnit::query()
                ->where('item_id', $item->getKey())
                ->where('unit_role', $data->unitRole->value)
                ->update(['is_default' => false]);
        }

        return ItemUnit::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'uom_id' => $data->uomId,
            'unit_role' => $data->unitRole,
            'conversion_factor' => $this->math->normalize($data->conversionFactor),
            'is_default' => $data->isDefault,
            'is_active' => $data->isActive,
        ]);
    }
}
