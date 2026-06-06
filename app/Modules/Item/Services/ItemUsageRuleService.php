<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Item\DTOs\ItemUsageRuleData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUsageRule;
use Modules\Item\Validators\ItemValidationService;

final class ItemUsageRuleService
{
    public function __construct(private readonly ItemValidationService $validator) {}

    public function set(Item $item, ItemUsageRuleData $data): ItemUsageRule
    {
        $this->validator->validateUsageRule($item, $data);

        if (ItemUsageRule::query()
            ->where('item_id', $item->getKey())
            ->where('module_code', $data->moduleCode)
            ->exists()) {
            throw new \InvalidArgumentException('Item usage rule already exists for this module.');
        }

        return ItemUsageRule::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'module_code' => $data->moduleCode,
            'is_enabled' => $data->isEnabled,
        ]);
    }

    public function update(Item $item, ItemUsageRule $rule, ItemUsageRuleData $data): ItemUsageRule
    {
        $this->assertBelongsToItem($item, $rule);
        $this->validator->validateUsageRule($item, $data);

        $duplicate = ItemUsageRule::query()
            ->where('item_id', $item->getKey())
            ->where('module_code', $data->moduleCode)
            ->whereKeyNot($rule->getKey())
            ->exists();
        if ($duplicate) {
            throw new \InvalidArgumentException('Item usage rule already exists for this module.');
        }

        $rule->fill([
            'module_code' => $data->moduleCode,
            'is_enabled' => $data->isEnabled,
        ])->save();

        return $rule->refresh();
    }

    public function delete(Item $item, ItemUsageRule $rule): void
    {
        $this->assertBelongsToItem($item, $rule);
        $rule->delete();
    }

    private function assertBelongsToItem(Item $item, ItemUsageRule $rule): void
    {
        if ((int) $rule->item_id !== (int) $item->getKey()) {
            throw new \InvalidArgumentException('Item usage rule does not belong to the item.');
        }
    }
}
