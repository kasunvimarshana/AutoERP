<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Item\DTOs\ItemVariantData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Item\Validators\ItemValidationService;

final class ItemVariantService
{
    public function __construct(private readonly ItemValidationService $validator) {}

    public function create(Item $item, ItemVariantData $data): ItemVariant
    {
        $this->validator->validateVariant($item, $data);

        return ItemVariant::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'code' => $data->code,
            'sku' => $data->sku,
            'barcode' => $data->barcode,
            'name' => $data->name,
            'attributes' => $data->attributes,
            'is_active' => $data->isActive,
        ]);
    }
}
