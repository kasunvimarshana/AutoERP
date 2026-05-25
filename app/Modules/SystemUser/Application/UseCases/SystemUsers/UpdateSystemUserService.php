<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\UseCases\SystemUsers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\UpdateSystemUserServiceInterface;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Domain\Constants\SystemUserErrorCode;
use Modules\SystemUser\Domain\Contracts\SystemUserDomainServiceInterface;
use Throwable;

final class UpdateSystemUserService implements UpdateSystemUserServiceInterface
{
    public function __construct(
        private readonly SystemUserRepositoryInterface $systemUsers,
        private readonly SystemUserDomainServiceInterface $domain,
    ) {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->systemUsers->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(SystemUserErrorCode::NOT_FOUND, 'System user not found.'));
            }

            $tenantId = array_key_exists('tenant_id', $payload)
                ? (int) $payload['tenant_id']
                : (int) $existing->require('tenant_id');

            if ($tenantId < 1) {
                return Result::failure(new Error(SystemUserErrorCode::INVALID_VALUE, 'Tenant id is required.'));
            }

            $existingUserId = $existing->get('user_id');
            $existingCode = $existing->get('code');
            $existingRegistrationNumber = $existing->get('registration_number');
            $existingStatus = $existing->get('status', config('system-user.defaults.status', 'active'));
            $existingNotes = $existing->get('notes');
            $userId = array_key_exists('user_id', $payload)
                ? (isset($payload['user_id']) ? (int) $payload['user_id'] : null)
                : (isset($existingUserId) ? (int) $existingUserId : null);

            if ($userId !== null) {
                $conflict = $this->systemUsers->findByTenantAndUserId($tenantId, $userId);
                if ($conflict !== null && (string) $conflict->id() !== (string) $existing->id()) {
                    return Result::failure(
                        new Error(SystemUserErrorCode::CONFLICT, 'System user already exists for tenant and user.')
                    );
                }
            }

            $record = $this->systemUsers->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => array_key_exists('organization_unit_id', $payload)
                    ? (
                        isset($payload['organization_unit_id'])
                            ? (int) $payload['organization_unit_id']
                            : null
                    )
                    : $existing->get('organization_unit_id'),
                'user_id' => $userId,
                'code' => array_key_exists('code', $payload)
                    ? $this->domain->normalizeOptionalText(
                        isset($payload['code']) ? (string) $payload['code'] : null,
                    )
                    : (
                        $this->domain->normalizeOptionalText(
                            isset($existingCode) ? (string) $existingCode : null,
                        )
                    ),
                'registration_number' => array_key_exists('registration_number', $payload)
                    ? $this->domain->normalizeOptionalText(
                        isset($payload['registration_number']) ? (string) $payload['registration_number'] : null,
                    )
                    : $this->domain->normalizeOptionalText(
                        isset($existingRegistrationNumber) ? (string) $existingRegistrationNumber : null,
                    ),
                'status' => array_key_exists('status', $payload)
                    ? $this->domain->normalizeStatus(isset($payload['status']) ? (string) $payload['status'] : null)
                    : $this->domain->normalizeStatus((string) $existingStatus),
                'notes' => array_key_exists('notes', $payload)
                    ? (
                        $this->domain->normalizeOptionalText(
                            isset($payload['notes']) ? (string) $payload['notes'] : null,
                        )
                    )
                    : (
                        $this->domain->normalizeOptionalText(
                            isset($existingNotes) ? (string) $existingNotes : null,
                        )
                    ),
                'created_by' => array_key_exists('created_by', $payload)
                    ? (isset($payload['created_by']) ? (int) $payload['created_by'] : null)
                    : $existing->get('created_by'),
                'updated_by' => array_key_exists('updated_by', $payload)
                    ? (isset($payload['updated_by']) ? (int) $payload['updated_by'] : null)
                    : $existing->get('updated_by'),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $this->domain->normalizeMetadata($existing->get('metadata', [])),
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SystemUserErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
