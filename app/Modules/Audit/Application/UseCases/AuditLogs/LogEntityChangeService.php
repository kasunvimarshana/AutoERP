<?php

declare(strict_types=1);

namespace Modules\Audit\Application\UseCases\AuditLogs;

use Modules\Audit\Application\DTOs\AuditLogActivityData;
use Modules\Audit\Application\DTOs\AuditLogEntityChangeData;
use Modules\Audit\Domain\Constants\AuditEventType;
use Modules\Core\Application\Results\Result;

final class LogEntityChangeService
{
    public function __construct(private readonly LogActivityService $logActivity) {}

    public function execute(AuditLogEntityChangeData $data): Result
    {
        $metadata = $data->metadata ?? [];
        $metadata['changed_fields'] = $this->detectChangedFields($data->oldValues, $data->newValues);

        return $this->logActivity->execute(new AuditLogActivityData(
            event: $data->event,
            auditableType: $data->entityType,
            auditableId: $data->entityId,
            tenantId: $data->tenantId,
            organizationUnitId: $data->organizationUnitId,
            userId: $data->userId,
            oldValues: $data->oldValues,
            newValues: $data->newValues,
            metadata: $metadata,
            url: $data->url,
            ipAddress: $data->ipAddress,
            userAgent: $data->userAgent,
            tags: $data->tags ?? [AuditEventType::ENTITY_CHANGE],
            occurredAt: $data->occurredAt,
        ));
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @return list<string>
     */
    private function detectChangedFields(?array $oldValues, ?array $newValues): array
    {
        $before = $oldValues ?? [];
        $after = $newValues ?? [];

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $changed = [];

        foreach ($keys as $key) {
            if (! is_string($key)) {
                continue;
            }

            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;

            if ($old !== $new) {
                $changed[] = $key;
            }
        }

        return array_values($changed);
    }
}
