<?php

declare(strict_types=1);

namespace Modules\Audit\Application\UseCases\AuditLogs;

use Modules\Audit\Application\Contracts\UseCases\AuditLogs\CaptureSystemEventAuditServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\LogActivityServiceInterface;
use Modules\Audit\Application\DTOs\AuditLogActivityData;
use Modules\Audit\Domain\Constants\AuditErrorCode;
use Modules\Audit\Domain\Constants\AuditEventType;
use Modules\Audit\Infrastructure\Support\AuditEventPayloadNormalizer;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Results\Result;
use Throwable;

final class CaptureSystemEventAuditService implements CaptureSystemEventAuditServiceInterface
{
    public function __construct(
        private readonly LogActivityServiceInterface $logActivity,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {
    }

    public function execute(string $eventName, mixed $eventPayload = null): Result
    {
        try {
            $normalized = AuditEventPayloadNormalizer::normalize($eventName, $eventPayload);

            return $this->logActivity->execute(new AuditLogActivityData(
                event: $normalized['event'],
                auditableType: $normalized['auditable_type'],
                auditableId: $normalized['auditable_id'],
                tenantId: $normalized['tenant_id'],
                organizationUnitId: $normalized['organization_unit_id'],
                userId: $normalized['user_id'],
                oldValues: $normalized['old_values'],
                newValues: $normalized['new_values'],
                metadata: $normalized['metadata'],
                url: $normalized['url'],
                ipAddress: $normalized['ip_address'],
                userAgent: $normalized['user_agent'],
                tags: [AuditEventType::SYSTEM_EVENT],
                occurredAt: $normalized['occurred_at'],
            ));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                AuditErrorCode::EVENT_CAPTURE_FAILED,
                ['event' => $eventName],
            ));
        }
    }
}
