<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Data\ConfigurationRevisionView;

final class ConfigurationRevisionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var ConfigurationRevisionView $revision */
        $revision = $this->resource;

        return [
            'id' => $revision->id,
            'operation' => $revision->operation,
            'scope' => $revision->scope,
            'tenant_id' => $revision->tenantId,
            'organization_unit_id' => $revision->organizationUnitId,
            'key' => $revision->key,
            'definition_version' => $revision->definitionVersion,
            'definition_compatible' => $revision->definitionCompatible,
            'value' => $revision->sensitive ? null : $revision->value,
            'display_value' => $revision->sensitive
                ? ($revision->configured ? 'Configured (protected)' : 'Not configured')
                : null,
            'configured' => $revision->configured,
            'sensitive' => $revision->sensitive,
            'resulting_row_version' => $revision->resultingRowVersion,
            'source_revision_id' => $revision->sourceRevisionId,
            'actor' => [
                'type' => $revision->actorType,
                'id' => $revision->actorId,
                'name' => $revision->actorName,
                'email' => $revision->actorEmail,
            ],
            'reason' => $revision->reason,
            'created_at' => $revision->createdAt->format(DATE_ATOM),
        ];
    }
}
