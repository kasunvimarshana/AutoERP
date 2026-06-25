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
            'definition_version' => $entry->definition->version,
            'value_type' => $entry->definition->valueType,
            'scope' => $entry->scope,
            'value' => $sensitive ? null : $entry->value,
            'display_value' => $sensitive ? 'Configured (protected)' : null,
            'effective_value' => $sensitive ? null : $entry->value,
            'effective_display_value' => $sensitive ? 'Configured (protected)' : null,
            'source_scope' => $entry->scope,
            'inherited_value' => $sensitive ? null : $entry->inheritedValue,
            'inherited_display_value' => $sensitive
                ? ($entry->inheritedConfigured ? 'Configured (protected)' : 'Not configured')
                : null,
            'inherited_configured' => $entry->inheritedConfigured,
            'inherited_source_scope' => $entry->inheritedSourceScope,
            'inherited_uses_default' => $entry->inheritedUsesDefault,
            'sensitive' => $sensitive,
            'runtime_mutable' => $entry->definition->runtimeMutable,
            'row_version' => $entry->rowVersion,
            'updated_at' => $entry->updatedAt->format(DATE_ATOM),
        ];
    }
}
