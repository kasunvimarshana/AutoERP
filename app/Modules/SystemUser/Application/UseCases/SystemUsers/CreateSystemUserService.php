<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\UseCases\SystemUsers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\CreateSystemUserServiceInterface;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Domain\Constants\SystemUserErrorCode;
use Modules\SystemUser\Domain\Contracts\SystemUserDomainServiceInterface;
use Throwable;

final class CreateSystemUserService implements CreateSystemUserServiceInterface
{
    public function __construct(
        private readonly SystemUserRepositoryInterface $systemUsers,
        private readonly SystemUserDomainServiceInterface $domain,
    ) {
    }

    public function execute(array $payload): Result
    {
        try {
            $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
            if ($tenantId < 1) {
                return Result::failure(new Error(SystemUserErrorCode::INVALID_VALUE, 'Tenant id is required.'));
            }

            $userId = array_key_exists('user_id', $payload)
                ? (isset($payload['user_id']) ? (int) $payload['user_id'] : null)
                : null;

            if ($userId !== null && $this->systemUsers->findByTenantAndUserId($tenantId, $userId) !== null) {
                return Result::failure(
                    new Error(SystemUserErrorCode::CONFLICT, 'System user already exists for tenant and user.')
                );
            }

            $record = $this->systemUsers->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => array_key_exists('organization_unit_id', $payload)
                    ? (isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null)
                    : null,
                'user_id' => $userId,
                'code' => $this->domain->normalizeOptionalText(
                    isset($payload['code']) ? (string) $payload['code'] : null,
                ),
                'registration_number' => $this->domain->normalizeOptionalText(
                    isset($payload['registration_number']) ? (string) $payload['registration_number'] : null,
                ),
                'status' => $this->domain->normalizeStatus(
                    isset($payload['status'])
                        ? (string) $payload['status']
                        : (string) config('system-user.defaults.status', 'active'),
                ),
                'notes' => $this->domain->normalizeOptionalText(
                    isset($payload['notes']) ? (string) $payload['notes'] : null,
                ),
                'created_by' => array_key_exists('created_by', $payload)
                    ? (isset($payload['created_by']) ? (int) $payload['created_by'] : null)
                    : null,
                'updated_by' => array_key_exists('updated_by', $payload)
                    ? (isset($payload['updated_by']) ? (int) $payload['updated_by'] : null)
                    : null,
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'row_version' => 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SystemUserErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
