<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\OrganizationUnit\Application\Actions\DeleteOrganizationUnitRecordAction;
use Modules\OrganizationUnit\Application\Actions\FindOrganizationUnitRecordAction;
use Modules\OrganizationUnit\Application\Actions\ListOrganizationUnitRecordsAction;
use Modules\OrganizationUnit\Application\Actions\PersistOrganizationUnitRecordAction;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitDocumentData;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitSettingData;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitSettingGroupData;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitTypeData;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitDocumentRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingGroupRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitRecordNotFoundException;
use Modules\OrganizationUnit\Domain\Services\OrganizationUnitDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class OrganizationUnitService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly OrganizationUnitTypeRepositoryInterface $types,
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly OrganizationUnitSettingGroupRepositoryInterface $settingGroups,
        private readonly OrganizationUnitSettingRepositoryInterface $settings,
        private readonly OrganizationUnitDocumentRepositoryInterface $documents,
        private readonly OrganizationUnitDomainService $domain,
        private readonly ListOrganizationUnitRecordsAction $listRecords,
        private readonly FindOrganizationUnitRecordAction $findRecord,
        private readonly PersistOrganizationUnitRecordAction $persistRecord,
        private readonly DeleteOrganizationUnitRecordAction $deleteRecord,
    ) {}

    public function listTypes(int|string $tenantId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findTenant($tenantId);

        return $perPage === null ? $this->types->getForTenant($tenantId) : $this->types->paginateForTenant($tenantId, $perPage);
    }

    public function findType(int|string $tenantId, int|string $id): Model
    {
        return $this->findTenantScoped($this->types->findForTenantById($tenantId, $id), 'Organization unit type', $id);
    }

    public function createType(OrganizationUnitTypeData $data): Model
    {
        $this->findTenant($data->tenantId);

        return $this->persistRecord->create($this->types, $this->typeAttributes($data));
    }

    public function updateType(int|string $tenantId, int|string $id, OrganizationUnitTypeData $data): Model
    {
        return $this->persistRecord->update($this->types, $this->findType($tenantId, $id), $this->typeAttributes($data));
    }

    public function deleteType(int|string $tenantId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->types, $this->findType($tenantId, $id));
    }

    public function listUnits(int|string $tenantId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findTenant($tenantId);

        return $perPage === null ? $this->units->getForTenant($tenantId) : $this->units->paginateForTenant($tenantId, $perPage);
    }

    public function findUnit(int|string $tenantId, int|string $id): Model
    {
        return $this->findTenantScoped($this->units->findForTenantById($tenantId, $id), 'Organization unit', $id);
    }

    public function createUnit(OrganizationUnitData $data): Model
    {
        $this->findTenant($data->tenantId);

        return $this->persistRecord->create($this->units, $this->unitAttributes($data));
    }

    public function updateUnit(int|string $tenantId, int|string $id, OrganizationUnitData $data): Model
    {
        return $this->persistRecord->update($this->units, $this->findUnit($tenantId, $id), $this->unitAttributes($data));
    }

    public function deleteUnit(int|string $tenantId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->units, $this->findUnit($tenantId, $id));
    }

    public function listSettingGroups(int|string $tenantId, int|string $unitId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findUnit($tenantId, $unitId);

        return $perPage === null ? $this->settingGroups->getForOrganizationUnit($unitId) : $this->settingGroups->paginateForOrganizationUnit($unitId, $perPage);
    }

    public function findSettingGroup(int|string $tenantId, int|string $unitId, int|string $id): Model
    {
        $record = $this->settingGroups->findForTenantById($tenantId, $id);

        if ($record === null || (int) $record->organization_unit_id !== (int) $unitId) {
            throw OrganizationUnitRecordNotFoundException::for('Organization unit setting group', $id);
        }

        return $record;
    }

    public function createSettingGroup(OrganizationUnitSettingGroupData $data): Model
    {
        $this->findUnit($data->tenantId, $data->organizationUnitId);

        return $this->persistRecord->create($this->settingGroups, $this->settingGroupAttributes($data));
    }

    public function updateSettingGroup(int|string $tenantId, int|string $unitId, int|string $id, OrganizationUnitSettingGroupData $data): Model
    {
        return $this->persistRecord->update($this->settingGroups, $this->findSettingGroup($tenantId, $unitId, $id), $this->settingGroupAttributes($data));
    }

    public function deleteSettingGroup(int|string $tenantId, int|string $unitId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->settingGroups, $this->findSettingGroup($tenantId, $unitId, $id));
    }

    public function listSettings(int|string $tenantId, int|string $unitId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findUnit($tenantId, $unitId);

        return $perPage === null ? $this->settings->getForOrganizationUnit($unitId) : $this->settings->paginateForOrganizationUnit($unitId, $perPage);
    }

    public function findSetting(int|string $tenantId, int|string $unitId, int|string $id): Model
    {
        $record = $this->settings->findForTenantById($tenantId, $id);

        if ($record === null || (int) $record->organization_unit_id !== (int) $unitId) {
            throw OrganizationUnitRecordNotFoundException::for('Organization unit setting', $id);
        }

        return $record;
    }

    public function createSetting(OrganizationUnitSettingData $data): Model
    {
        $this->findUnit($data->tenantId, $data->organizationUnitId);

        return $this->persistRecord->create($this->settings, $this->settingAttributes($data));
    }

    public function updateSetting(int|string $tenantId, int|string $unitId, int|string $id, OrganizationUnitSettingData $data): Model
    {
        return $this->persistRecord->update($this->settings, $this->findSetting($tenantId, $unitId, $id), $this->settingAttributes($data));
    }

    public function deleteSetting(int|string $tenantId, int|string $unitId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->settings, $this->findSetting($tenantId, $unitId, $id));
    }

    public function listDocuments(int|string $tenantId, int|string $unitId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findUnit($tenantId, $unitId);

        return $perPage === null ? $this->documents->getForOrganizationUnit($unitId) : $this->documents->paginateForOrganizationUnit($unitId, $perPage);
    }

    public function findDocument(int|string $tenantId, int|string $unitId, int|string $id): Model
    {
        $record = $this->documents->findForTenantById($tenantId, $id);

        if ($record === null || (int) $record->organization_unit_id !== (int) $unitId) {
            throw OrganizationUnitRecordNotFoundException::for('Organization unit document', $id);
        }

        return $record;
    }

    public function createDocument(OrganizationUnitDocumentData $data): Model
    {
        $this->findUnit($data->tenantId, $data->organizationUnitId);

        return $this->persistRecord->create($this->documents, $this->documentAttributes($data));
    }

    public function updateDocument(int|string $tenantId, int|string $unitId, int|string $id, OrganizationUnitDocumentData $data): Model
    {
        return $this->persistRecord->update($this->documents, $this->findDocument($tenantId, $unitId, $id), $this->documentAttributes($data));
    }

    public function deleteDocument(int|string $tenantId, int|string $unitId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->documents, $this->findDocument($tenantId, $unitId, $id));
    }

    private function findTenant(int|string $tenantId): Model
    {
        return $this->findRecord->execute($this->tenants, 'Tenant', $tenantId);
    }

    private function findTenantScoped(?Model $record, string $resource, int|string $id): Model
    {
        if ($record === null) {
            throw OrganizationUnitRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }

    private function typeAttributes(OrganizationUnitTypeData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'name' => $this->domain->normalizeText($data->name),
            'level' => $data->level,
            'is_active' => $data->isActive,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    private function unitAttributes(OrganizationUnitData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'type_id' => $data->typeId,
            'parent_id' => $data->parentId,
            'name' => $this->domain->normalizeText($data->name),
            'code' => $this->domain->normalizeCode($data->code),
            'image_path' => $this->domain->normalizeText($data->imagePath),
            'path' => $this->domain->normalizePath($data->path),
            'depth' => $data->depth,
            'is_active' => $data->isActive,
            'description' => $this->domain->normalizeText($data->description),
            '_lft' => $data->left,
            '_rgt' => $data->right,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    private function settingGroupAttributes(OrganizationUnitSettingGroupData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'key' => $this->domain->normalizeText($data->key),
            'value' => $this->domain->normalizeText($data->value),
            'parent_id' => $data->parentId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    private function settingAttributes(OrganizationUnitSettingData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'group_id' => $data->groupId,
            'key' => $this->domain->normalizeText($data->key),
            'value' => $this->domain->normalizeText($data->value),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    private function documentAttributes(OrganizationUnitDocumentData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'name' => $this->domain->normalizeText($data->name),
            'file_path' => $this->domain->normalizeText($data->filePath),
            'mime_type' => $this->domain->normalizeText($data->mimeType),
            'size' => $data->size,
            'type' => $this->domain->normalizeText($data->type),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
