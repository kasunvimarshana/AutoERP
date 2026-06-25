<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Data\ConfigurationRevisionView;

final class ConfigurationRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ConfigurationRevisionView $revision */
        $revision = $this->resource;

        return [
            'id' => $revision->id,
            'scope' => $revision->scope,
            'key' => $revision->key,
            'action' => $revision->action,
            'value_type' => $revision->valueType,
            'sensitive' => $revision->sensitive,
            'before_exists' => $revision->beforeExists,
            'before_value' => $revision->sensitive ? null : $revision->beforeValue,
            'after_exists' => $revision->afterExists,
            'after_value' => $revision->sensitive ? null : $revision->afterValue,
            'entry_row_version' => $revision->entryRowVersion,
            'changed_by_name' => $revision->changedByName ?? 'System',
            'created_at' => $revision->createdAt->format(DATE_ATOM),
        ];
    }
}
