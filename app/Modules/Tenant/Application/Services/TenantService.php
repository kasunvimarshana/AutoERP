<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Application\Actions\DeleteTenantRecordAction;
use Modules\Tenant\Application\Actions\FindTenantRecordAction;
use Modules\Tenant\Application\Actions\ListTenantRecordsAction;
use Modules\Tenant\Application\Actions\PersistTenantRecordAction;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Application\DTOs\TenantDocumentData;
use Modules\Tenant\Application\DTOs\TenantDomainData;
use Modules\Tenant\Application\DTOs\TenantPlanData;
use Modules\Tenant\Application\DTOs\TenantSettingData;
use Modules\Tenant\Application\DTOs\TenantSettingGroupData;
use Modules\Tenant\Application\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingGroupRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingRepositoryInterface;
use Modules\Tenant\Domain\Exceptions\TenantRecordNotFoundException;
use Modules\Tenant\Domain\Services\TenantDomainService;

class TenantService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantSettingGroupRepositoryInterface $settingGroups,
        private readonly TenantSettingRepositoryInterface $settings,
        private readonly TenantDocumentRepositoryInterface $documents,
        private readonly TenantDomainService $domain,
        private readonly ListTenantRecordsAction $listRecords,
        private readonly FindTenantRecordAction $findRecord,
        private readonly PersistTenantRecordAction $persistRecord,
        private readonly DeleteTenantRecordAction $deleteRecord,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listPlans(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->plans, $filters, $perPage);
    }

    public function findPlan(int|string $id): Model
    {
        return $this->findRecord->execute($this->plans, 'Tenant plan', $id);
    }

    public function createPlan(TenantPlanData $data): Model
    {
        return $this->persistRecord->create($this->plans, $this->planAttributes($data));
    }

    public function updatePlan(int|string $id, TenantPlanData $data): Model
    {
        return $this->persistRecord->update($this->plans, $this->findPlan($id), $this->planAttributes($data));
    }

    public function deletePlan(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->plans, $this->findPlan($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listTenants(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->tenants, $filters, $perPage);
    }

    public function findTenant(int|string $id): Model
    {
        return $this->findRecord->execute($this->tenants, 'Tenant', $id);
    }

    public function createTenant(TenantData $data): Model
    {
        return $this->persistRecord->create($this->tenants, $this->tenantAttributes($data));
    }

    public function updateTenant(int|string $id, TenantData $data): Model
    {
        return $this->persistRecord->update($this->tenants, $this->findTenant($id), $this->tenantAttributes($data));
    }

    public function deleteTenant(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->tenants, $this->findTenant($id));
    }

    public function listDomains(int|string $tenantId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findTenant($tenantId);

        return $perPage === null
            ? $this->domains->getForTenant($tenantId)
            : $this->domains->paginateForTenant($tenantId, $perPage);
    }

    public function findDomain(int|string $tenantId, int|string $id): Model
    {
        $record = $this->domains->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw TenantRecordNotFoundException::for('Tenant domain', $id);
        }

        return $record;
    }

    public function createDomain(TenantDomainData $data): Model
    {
        $this->findTenant($data->tenantId);

        return $this->persistRecord->create($this->domains, $this->domainAttributes($data));
    }

    public function updateDomain(int|string $tenantId, int|string $id, TenantDomainData $data): Model
    {
        return $this->persistRecord->update($this->domains, $this->findDomain($tenantId, $id), $this->domainAttributes($data));
    }

    public function deleteDomain(int|string $tenantId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->domains, $this->findDomain($tenantId, $id));
    }

    public function listSettingGroups(int|string $tenantId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findTenant($tenantId);

        return $perPage === null
            ? $this->settingGroups->getForTenant($tenantId)
            : $this->settingGroups->paginateForTenant($tenantId, $perPage);
    }

    public function findSettingGroup(int|string $tenantId, int|string $id): Model
    {
        $record = $this->settingGroups->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw TenantRecordNotFoundException::for('Tenant setting group', $id);
        }

        return $record;
    }

    public function createSettingGroup(TenantSettingGroupData $data): Model
    {
        $this->findTenant($data->tenantId);

        return $this->persistRecord->create($this->settingGroups, $this->settingGroupAttributes($data));
    }

    public function updateSettingGroup(int|string $tenantId, int|string $id, TenantSettingGroupData $data): Model
    {
        return $this->persistRecord->update($this->settingGroups, $this->findSettingGroup($tenantId, $id), $this->settingGroupAttributes($data));
    }

    public function deleteSettingGroup(int|string $tenantId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->settingGroups, $this->findSettingGroup($tenantId, $id));
    }

    public function listSettings(int|string $tenantId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findTenant($tenantId);

        return $perPage === null
            ? $this->settings->getForTenant($tenantId)
            : $this->settings->paginateForTenant($tenantId, $perPage);
    }

    public function findSetting(int|string $tenantId, int|string $id): Model
    {
        $record = $this->settings->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw TenantRecordNotFoundException::for('Tenant setting', $id);
        }

        return $record;
    }

    public function createSetting(TenantSettingData $data): Model
    {
        $this->findTenant($data->tenantId);

        return $this->persistRecord->create($this->settings, $this->settingAttributes($data));
    }

    public function updateSetting(int|string $tenantId, int|string $id, TenantSettingData $data): Model
    {
        return $this->persistRecord->update($this->settings, $this->findSetting($tenantId, $id), $this->settingAttributes($data));
    }

    public function deleteSetting(int|string $tenantId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->settings, $this->findSetting($tenantId, $id));
    }

    public function listDocuments(int|string $tenantId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->findTenant($tenantId);

        return $perPage === null
            ? $this->documents->getForTenant($tenantId)
            : $this->documents->paginateForTenant($tenantId, $perPage);
    }

    public function findDocument(int|string $tenantId, int|string $id): Model
    {
        $record = $this->documents->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw TenantRecordNotFoundException::for('Tenant document', $id);
        }

        return $record;
    }

    public function createDocument(TenantDocumentData $data): Model
    {
        $this->findTenant($data->tenantId);

        return $this->persistRecord->create($this->documents, $this->documentAttributes($data));
    }

    public function updateDocument(int|string $tenantId, int|string $id, TenantDocumentData $data): Model
    {
        return $this->persistRecord->update($this->documents, $this->findDocument($tenantId, $id), $this->documentAttributes($data));
    }

    public function deleteDocument(int|string $tenantId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->documents, $this->findDocument($tenantId, $id));
    }

    /**
     * @return array<string, mixed>
     */
    private function planAttributes(TenantPlanData $data): array
    {
        $this->domain->assertBillingInterval($data->billingInterval);
        $this->domain->assertNonNegativePrice($data->price);

        return [
            'name' => $this->domain->normalizeText($data->name),
            'slug' => $this->domain->normalizeSlug($data->slug),
            'features' => $this->domain->normalizeMetadata($data->features),
            'limits' => $this->domain->normalizeMetadata($data->limits),
            'price' => $data->price,
            'currency_id' => $data->currencyId,
            'billing_interval' => $data->billingInterval,
            'is_active' => $data->isActive,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantAttributes(TenantData $data): array
    {
        $this->domain->assertTenantStatus($data->status);

        return [
            'name' => $this->domain->normalizeText($data->name),
            'slug' => $this->domain->normalizeSlug($data->slug),
            'logo_path' => $this->domain->normalizeText($data->logoPath),
            'cross_org_transactions' => $data->crossOrgTransactions,
            'tenant_plan_id' => $data->tenantPlanId,
            'currency_id' => $data->currencyId,
            'status' => $data->status,
            'trial_ends_at' => $data->trialEndsAt,
            'subscription_ends_at' => $data->subscriptionEndsAt,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function domainAttributes(TenantDomainData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'domain' => $this->domain->normalizeDomain($data->domain),
            'is_primary' => $data->isPrimary,
            'is_verified' => $data->isVerified,
            'verified_at' => $data->verifiedAt,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settingGroupAttributes(TenantSettingGroupData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'key' => $this->domain->normalizeText($data->key),
            'value' => $this->domain->normalizeText($data->value),
            'parent_id' => $data->parentId,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settingAttributes(TenantSettingData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'group_id' => $data->groupId,
            'key' => $this->domain->normalizeText($data->key),
            'value' => $this->domain->normalizeText($data->value),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documentAttributes(TenantDocumentData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'name' => $this->domain->normalizeText($data->name),
            'file_path' => $this->domain->normalizeText($data->filePath),
            'mime_type' => $this->domain->normalizeText($data->mimeType),
            'size' => $data->size,
            'type' => $this->domain->normalizeText($data->type),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
