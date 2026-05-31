<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests\Concerns;

use Illuminate\Validation\Validator;
use Modules\Item\Application\Support\ItemUomOptions;

trait ValidatesInventoryItemUoms
{
    protected function addItemUomErrorWhenInvalid(Validator $validator, int $tenantId, mixed $itemId, mixed $uomId, string $errorKey): void
    {
        if ($tenantId < 1 || ! is_numeric($itemId) || ! is_numeric($uomId)) {
            return;
        }

        if (! ItemUomOptions::isAllowed($tenantId, (int) $itemId, (int) $uomId, 'inventory')) {
            $validator->errors()->add($errorKey, 'The selected UOM is not configured for this item in inventory context.');
        }
    }
}
