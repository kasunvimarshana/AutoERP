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
            'unit_cost' => $this->math->normalize($data->unitCost),
            'default_workforce_role' => $data->defaultWorkforceRole,
            'is_required' => $data->isRequired,
            'sort_order' => $data->sortOrder,
        ]);
    }

    public function update(Item $parent, ItemBundle $line, ItemBundleData $data): ItemBundle
    {
        $this->assertBelongsToItem($parent, $line);
        $this->validator->validateBundle($parent, $data);
        $line->fill([
            'child_item_id' => $data->childItemId,
            'child_variant_id' => $data->childVariantId,
            'quantity' => $this->math->normalize($data->quantity),
            'uom_id' => $data->uomId,
            'line_type' => $data->lineType,
            'unit_cost' => $this->math->normalize($data->unitCost),
            'default_workforce_role' => $data->defaultWorkforceRole,
            'is_required' => $data->isRequired,
            'sort_order' => $data->sortOrder,
        ])->save();

        return $line->refresh()->load(['childItem', 'childVariant', 'uom']);
    }

    public function delete(Item $parent, ItemBundle $line): void
    {
        $this->assertBelongsToItem($parent, $line);
        $line->delete();
    }

    private function assertBelongsToItem(Item $parent, ItemBundle $line): void
    {
        if ((int) $line->parent_item_id !== (int) $parent->getKey()) {
            throw new \InvalidArgumentException('Item bundle line does not belong to the item.');
        }
    }
}
