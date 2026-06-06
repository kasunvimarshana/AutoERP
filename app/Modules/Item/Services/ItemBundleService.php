<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\ItemBundleData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBundle;
use Modules\Item\Validators\ItemValidationService;

final class ItemBundleService
{
    public function __construct(
        private readonly ItemValidationService $validator,
        private readonly DecimalMath $math,
    ) {}

    public function addLine(Item $parent, ItemBundleData $data): ItemBundle
    {
        $this->validator->validateBundle($parent, $data);

        return ItemBundle::query()->create([
            'tenant_id' => $parent->tenant_id,
            'organization_unit_id' => $parent->organization_unit_id,
            'parent_item_id' => $parent->getKey(),
            'child_item_id' => $data->childItemId,
            'child_variant_id' => $data->childVariantId,
            'quantity' => $this->math->normalize($data->quantity),
            'uom_id' => $data->uomId,
            'line_type' => $data->lineType,
            'is_required' => $data->isRequired,
            'sort_order' => $data->sortOrder,
        ]);
    }
}
