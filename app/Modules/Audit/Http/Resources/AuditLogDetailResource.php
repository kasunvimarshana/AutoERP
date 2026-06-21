<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;
use Modules\Core\DTOs\DataRecord;

final class AuditLogDetailResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly bool $includeSensitive)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $record = $this->record();
        $summary = (new AuditLogSummaryResource($record))->toArray($request);

        $data = [
            ...$summary,
            'tenant' => [
                'id' => $record->get('tenant_id'),
                'name' => $record->get('tenant_name'),
            ],
            'source' => [
                'module' => $record->get('source_module'),
                'type' => $record->get('source_type'),
                'id' => $record->get('source_id'),
                'reference' => $record->get('source_reference'),
            ],
            'sensitive_details_visible' => $this->includeSensitive,
        ];

        if (! $this->includeSensitive) {
            return $data;
        }

        return [
            ...$data,
            'producer_key' => $record->get('producer_key'),
            'changes' => $record->get('changes'),
            'metadata' => $record->get('metadata'),
            'request' => [
                'id' => $record->get('request_id'),
                'method' => $record->get('request_method'),
                'route_name' => $record->get('route_name'),
                'route_path' => $record->get('route_path'),
                'ip_address' => $record->get('ip_address'),
                'user_agent' => $record->get('user_agent'),
                'actor_guard' => $record->get('actor_guard'),
                'actor_provider' => $record->get('actor_provider'),
                'application_id' => $record->get('application_id'),
                'impersonator_user_id' => $record->get('impersonator_user_id'),
            ],
        ];
    }

    private function record(): DataRecord
    {
        if (! $this->resource instanceof DataRecord) {
            throw new LogicException('AuditLogDetailResource requires a DataRecord.');
        }

        return $this->resource;
    }
}
