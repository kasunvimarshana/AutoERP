<?php

declare(strict_types=1);

namespace Modules\Audit\Application\UseCases\AuditLogs;

use Modules\Audit\Application\Contracts\UseCases\AuditLogs\LogActivityServiceInterface;
use Modules\Audit\Application\DTOs\AuditLogActivityData;
use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Domain\Constants\AuditErrorCode;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Results\Result;
use Throwable;

final class LogActivityService implements LogActivityServiceInterface
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly ErrorNormalizerInterface $errorNormalizer,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {
    }

    public function execute(AuditLogActivityData $data): Result
    {
        try {
            $attributes = [
                'row_version' => 1,
                'tenant_id' => $data->tenantId ?? $this->currentTenant->currentTenantId(),
                'organization_unit_id' => $data->organizationUnitId
                    ?? $this->currentOrganizationUnit->currentOrganizationUnitId(),
                'user_id' => $data->userId ?? $this->currentUser->currentUserId(),
                'event' => trim($data->event),
                'auditable_type' => trim($data->auditableType),
                'auditable_id' => trim($data->auditableId),
                'old_values' => $data->oldValues,
                'new_values' => $data->newValues,
                'metadata' => $data->metadata,
                'url' => $this->nullableTrim($data->url),
                'ip_address' => $this->nullableTrim($data->ipAddress),
                'user_agent' => $this->nullableTrim($data->userAgent),
                'tags' => $data->tags,
                'occurred_at' => $this->nullableTrim($data->occurredAt),
            ];

            if (
                $attributes['event'] === ''
                || $attributes['auditable_type'] === ''
                || $attributes['auditable_id'] === ''
            ) {
                $message = 'Event, auditable_type and auditable_id are required.';

                throw new \InvalidArgumentException($message);
            }

            $record = $this->transactionManager->runInTransaction(
                fn () => $this->repository->append($attributes),
            );

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                AuditErrorCode::LOG_WRITE_FAILED,
            ));
        }
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $resolved = trim($value);

        return $resolved !== '' ? $resolved : null;
    }
}
