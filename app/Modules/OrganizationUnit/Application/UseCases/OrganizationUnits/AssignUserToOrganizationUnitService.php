<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases\OrganizationUnits;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\Support\OrganizationUnitContext;
use Modules\OrganizationUnit\Domain\Constants\OrganizationUnitErrorCode;
use Modules\User\Application\UseCases\UserTenantService;
use Throwable;

final class AssignUserToOrganizationUnitService
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly UserTenantService $userTenants,
        private readonly OrganizationUnitContext $organizationUnitContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(int|string $organizationUnitId, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($organizationUnitId, $payload): Result {
                $resolvedTenantId = $this->organizationUnitContext->resolveTenantId(
                    $this->toNullableInt($payload['tenant_id'] ?? null),
                );

                $unitId = $this->toNullableInt($organizationUnitId);
                if ($unitId === null) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::INVALID_VALUE,
                        'Organization unit identifier is invalid.',
                    ));
                }

                $unit = $this->units->findById($unitId);
                if ($unit === null || (int) $unit->require('tenant_id') !== $resolvedTenantId) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::NOT_FOUND,
                        'Organization unit not found.',
                    ));
                }

                $userId = $this->toNullableInt($payload['user_id'] ?? null);
                if ($userId === null) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::INVALID_VALUE,
                        'User identifier is required.',
                    ));
                }

                return $this->userTenants->create([
                    'tenant_id' => $resolvedTenantId,
                    'organization_unit_id' => $unitId,
                    'user_id' => $userId,
                    'role_id' => $this->toNullableInt($payload['role_id'] ?? null),
                    'is_default' => (bool) ($payload['is_default'] ?? false),
                    'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : null,
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                OrganizationUnitErrorCode::INVALID_VALUE,
                [
                    'operation' => 'organization-unit.assign-user',
                    'organization_unit_id' => (string) $organizationUnitId,
                ],
            ));
        }
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
