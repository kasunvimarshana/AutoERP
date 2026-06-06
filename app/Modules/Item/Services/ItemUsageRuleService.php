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

        /** @var ItemUsageRule $rule */
        $rule = ItemUsageRule::query()->updateOrCreate(
            [
                'tenant_id' => $item->tenant_id,
                'item_id' => $item->getKey(),
                'module_code' => $data->moduleCode,
            ],
            [
                'organization_unit_id' => $item->organization_unit_id,
                'is_enabled' => $data->isEnabled,
            ],
        );

        return $rule;
    }
}
