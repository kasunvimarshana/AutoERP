<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases\OrganizationUnits;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\Support\OrganizationUnitContext;
use Modules\OrganizationUnit\Domain\Constants\OrganizationUnitErrorCode;
use Throwable;

final class ResolveOrganizationUnitService
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly OrganizationUnitContext $organizationUnitContext,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(?int $organizationUnitId = null): Result
    {
        try {
            $resolvedTenantId = $this->organizationUnitContext->requireTenantId();
            $resolvedOrganizationUnitId = $organizationUnitId
                ?? $this->organizationUnitContext->currentOrganizationUnitId();

            if ($resolvedOrganizationUnitId === null || $resolvedOrganizationUnitId < 1) {
                return Result::failure(new Error(
                    OrganizationUnitErrorCode::NOT_FOUND,
                    'Organization unit context is not available.',
                ));
            }

            $record = $this->units->findById($resolvedOrganizationUnitId);
            if ($record === null || (int) $record->require('tenant_id') !== $resolvedTenantId) {
                return Result::failure(new Error(
                    OrganizationUnitErrorCode::NOT_FOUND,
                    'Organization unit not found.',
                ));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                OrganizationUnitErrorCode::INVALID_VALUE,
                ['operation' => 'organization-unit.resolve'],
            ));
        }
    }
}
