<?php

declare(strict_types=1);

namespace Modules\Audit\Application\UseCases\AuditLogs;

use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Domain\Constants\AuditErrorCode;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetAuditLogService
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(AuditErrorCode::NOT_FOUND, 'AuditLog not found.'));
            }

            $tenantId = $this->currentTenant->currentTenantId();
            $organizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId();

            if ($tenantId !== null && (int) ($record->get('tenant_id') ?? 0) !== $tenantId) {
                return Result::failure(new Error(AuditErrorCode::NOT_FOUND, 'AuditLog not found.'));
            }

            if (
                $organizationUnitId !== null
                && (int) ($record->get('organization_unit_id') ?? 0) !== $organizationUnitId
            ) {
                return Result::failure(new Error(AuditErrorCode::NOT_FOUND, 'AuditLog not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                AuditErrorCode::INVALID_VALUE,
            ));
        }
    }
}
