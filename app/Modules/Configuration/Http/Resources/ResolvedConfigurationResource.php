<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Data\ResolvedConfigurationValue;

final class ResolvedConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ResolvedConfigurationValue $resolved */
        $resolved = $this->resource;
        $sensitive = $resolved->definition->sensitive;

        return [
            'key' => $resolved->definition->key,
            'label' => $resolved->definition->label,
            'owner' => $resolved->definition->owner,
            'value_type' => $resolved->definition->valueType,
            'value' => $sensitive ? null : $resolved->value,
            'display_value' => $sensitive ? 'Protected value' : null,
            'sensitive' => $sensitive,
            'source_scope' => $resolved->sourceScope,
            'uses_default' => $resolved->usesDefault,
            'row_version' => $resolved->rowVersion,
        ];
    }
}
