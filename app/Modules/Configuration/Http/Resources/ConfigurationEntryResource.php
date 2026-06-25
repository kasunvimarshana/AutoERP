<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Data\ConfigurationEntryView;

final class ConfigurationEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ConfigurationEntryView $entry */
        $entry = $this->resource;
        $sensitive = $entry->definition->sensitive;

        return [
            'key' => $entry->definition->key,
            'label' => $entry->definition->label,
            'description' => $entry->definition->description,
            'owner' => $entry->definition->owner,
            'value_type' => $entry->definition->valueType,
            'scope' => $entry->scope,
            'value' => $sensitive ? null : $entry->value,
            'display_value' => $sensitive ? 'Protected value' : null,
            'sensitive' => $sensitive,
            'row_version' => $entry->rowVersion,
            'updated_at' => $entry->updatedAt->format(DATE_ATOM),
        ];
    }
}
