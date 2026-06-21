<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Data\ConfigurationDefinition;

final class ConfigurationDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ConfigurationDefinition $definition */
        $definition = $this->resource;

        return [
            'key' => $definition->key,
            'label' => $definition->label,
            'description' => $definition->description,
            'owner' => $definition->owner,
            'value_type' => $definition->valueType,
            'allowed_scopes' => $definition->allowedScopes,
            'default_value' => $definition->sensitive ? null : $definition->defaultValue,
            'nullable' => $definition->nullable,
            'sensitive' => $definition->sensitive,
            'runtime_mutable' => $definition->runtimeMutable,
            'options' => $definition->options,
            'minimum' => $definition->minimum,
            'maximum' => $definition->maximum,
            'lookup' => $definition->lookup,
        ];
    }
}
