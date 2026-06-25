<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;
use Modules\Core\DTOs\DataRecord;

final class AuditLogSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $record = $this->record();

        return [
            'id' => (int) $record->require('id'),
            'event_uuid' => (string) $record->require('event_uuid'),
            'event_category' => (string) $record->require('event_category'),
            'event_name' => (string) $record->require('event_name'),
            'source_module' => (string) $record->require('source_module'),
            'actor' => [
                'type' => (string) $record->require('actor_type'),
                'id' => $record->get('actor_id'),
                'name' => $record->get('actor_name'),
            ],
            'subject' => [
                'type' => (string) $record->require('subject_type'),
                'id' => (string) $record->require('subject_id'),
                'reference' => $record->get('subject_reference'),
            ],
            'tenant' => [
                'id' => $record->get('tenant_id'),
                'name' => $record->get('tenant_name'),
            ],
            'organization_unit' => [
                'id' => $record->get('organization_unit_id'),
                'name' => $record->get('organization_unit_name'),
            ],
            'tags' => $record->get('tags') ?? [],
            'occurred_at' => $record->get('occurred_at'),
            'recorded_at' => $record->get('recorded_at'),
        ];
    }

    private function record(): DataRecord
    {
        if (! $this->resource instanceof DataRecord) {
            throw new LogicException('AuditLogSummaryResource requires a DataRecord.');
        }

        return $this->resource;
    }
}
