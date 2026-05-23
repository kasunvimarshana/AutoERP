<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\SystemUser\Application\Actions\DeleteSystemUserRecordAction;
use Modules\SystemUser\Application\Actions\FindSystemUserRecordAction;
use Modules\SystemUser\Application\Actions\ListSystemUserRecordsAction;
use Modules\SystemUser\Application\Actions\PersistSystemUserRecordAction;
use Modules\SystemUser\Application\DTOs\SystemUserData;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Domain\Exceptions\SystemUserRecordNotFoundException;
use Modules\SystemUser\Domain\Services\SystemUserDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\User\Application\Repositories\UserRepositoryInterface;

class SystemUserService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly UserRepositoryInterface $users,
        private readonly SystemUserRepositoryInterface $systemUsers,
        private readonly SystemUserDomainService $domain,
        private readonly ListSystemUserRecordsAction $listRecords,
        private readonly FindSystemUserRecordAction $findRecord,
        private readonly PersistSystemUserRecordAction $persistRecord,
        private readonly DeleteSystemUserRecordAction $deleteRecord,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listSystemUsers(
        int|string $tenantId,
        array $filters = [],
        ?int $perPage = null,
    ): Collection|LengthAwarePaginator {
        $this->findTenant($tenantId);

        return $this->listRecords->execute(
            $this->systemUsers,
            array_merge(['tenant_id' => (int) $tenantId], $filters),
            $perPage,
        );
    }

    public function findSystemUser(int|string $tenantId, int|string $id): Model
    {
        $record = $this->systemUsers->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw SystemUserRecordNotFoundException::for('System user', $id);
        }

        return $record;
    }

    public function createSystemUser(SystemUserData $data): Model
    {
        $this->findTenant($data->tenantId);
        $this->assertUserBelongsToTenant($data->tenantId, $data->userId);

        return $this->persistRecord->create($this->systemUsers, $this->attributes($data));
    }

    public function updateSystemUser(int|string $tenantId, int|string $id, SystemUserData $data): Model
    {
        $this->assertUserBelongsToTenant($tenantId, $data->userId);

        return $this->persistRecord->update(
            $this->systemUsers,
            $this->findSystemUser($tenantId, $id),
            $this->attributes($data),
        );
    }

    public function deleteSystemUser(int|string $tenantId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->systemUsers, $this->findSystemUser($tenantId, $id));
    }

    private function findTenant(int|string $tenantId): Model
    {
        return $this->findRecord->execute($this->tenants, 'Tenant', $tenantId);
    }

    private function assertUserBelongsToTenant(int|string $tenantId, ?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $user = $this->users->findForTenantById($tenantId, $userId);

        if ($user === null) {
            throw SystemUserRecordNotFoundException::for('User', $userId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(SystemUserData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'user_id' => $data->userId,
            'code' => $this->domain->normalizeCode($data->code),
            'registration_number' => $this->domain->normalizeOptionalText($data->registrationNumber),
            'status' => $this->domain->normalizeStatus($data->status),
            'notes' => $this->domain->normalizeOptionalText($data->notes),
            'created_by' => $data->createdBy,
            'updated_by' => $data->updatedBy,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
