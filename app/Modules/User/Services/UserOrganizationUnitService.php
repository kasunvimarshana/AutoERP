<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\User\Constants\UserErrorCode;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\User\Repositories\UserOrganizationUnitRepositoryInterface;
use Modules\User\Repositories\UserRepositoryInterface;
use Modules\User\Services\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserOrganizationUnitService extends AbstractUserCrudService
{
    public function __construct(
        private readonly UserOrganizationUnitRepositoryInterface $assignments,
        private readonly UserRepositoryInterface $users,
        private readonly OrganizationUnitRepositoryInterface $organizationUnits,
        private readonly TenantRepositoryInterface $tenants,
        private readonly UserDomainServiceInterface $domain,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function assign(array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($payload): Result {
                $tenantId = $this->toNullableInt($payload['tenant_id'] ?? null);
                $organizationUnitId = $this->toNullableInt($payload['organization_unit_id'] ?? null);
                $userId = $this->toNullableInt($payload['user_id'] ?? null);
                if ($tenantId === null || $organizationUnitId === null || $userId === null) {
                    return $this->failure(
                        UserErrorCode::INVALID_VALUE,
                        'Tenant, user, and organization unit are required.',
                    );
                }

                if ($this->tenants->lockById($tenantId) === null) {
                    return $this->failure(UserErrorCode::TENANT_REQUIRED, 'Tenant not found.');
                }

                $user = $this->users->findById($userId);
                if ($user === null || (int) $user->get('tenant_id') !== $tenantId) {
                    return $this->failure(UserErrorCode::TENANT_MISMATCH, 'User does not belong to the tenant.');
                }

                $organizationUnit = $this->organizationUnits->findById($organizationUnitId);
                if ($organizationUnit === null || (int) $organizationUnit->get('tenant_id') !== $tenantId) {
                    return $this->failure(
                        UserErrorCode::TENANT_MISMATCH,
                        'Organization unit does not belong to the tenant.',
                    );
                }

                if ($this->assignments->findAssignment($tenantId, $organizationUnitId, $userId) !== null) {
                    return $this->failure(
                        UserErrorCode::DUPLICATE_ORGANIZATION_ASSIGNMENT,
                        'User is already assigned to this organization unit.',
                    );
                }

                $status = strtolower(trim((string) ($payload['status'] ?? UserOrganizationUnitStatus::ACTIVE)));
                if (! in_array($status, [
                    UserOrganizationUnitStatus::ACTIVE,
                    UserOrganizationUnitStatus::INACTIVE,
                    UserOrganizationUnitStatus::REVOKED,
                ], true)) {
                    return $this->failure(UserErrorCode::INVALID_VALUE, 'Organization assignment status is invalid.');
                }

                $isDefault = $status === UserOrganizationUnitStatus::ACTIVE
                    && $this->toBool($payload['is_default'] ?? false);
                if ($isDefault) {
                    $this->assignments->clearDefaultForUser($tenantId, $userId);
                }

                $assignment = $this->assignments->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'user_id' => $userId,
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                    'status' => $status,
                    'is_default' => $isDefault,
                    'default_marker' => $isDefault ? 'default' : null,
                    'row_version' => 1,
                ]);

                return $this->success($assignment);
            });
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }
}
