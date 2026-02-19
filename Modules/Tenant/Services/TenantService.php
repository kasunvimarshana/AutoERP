<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Support\Str;
use Modules\Audit\Services\AuditService;
use Modules\Core\Exceptions\BusinessRuleException;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Repositories\TenantRepository;

/**
 * Tenant Service
 *
 * Handles business logic for tenant management including creation,
 * updates, deletion, provisioning, and audit logging.
 */
class TenantService
{
    /**
     * Allowed fields for tenant updates
     */
    private const ALLOWED_UPDATE_FIELDS = ['name', 'slug', 'domain', 'settings', 'is_active'];

    /**
     * Create a new TenantService instance
     */
    public function __construct(
        protected TenantRepository $tenantRepository,
        protected AuditService $auditService
    ) {}

    /**
     * Create a new tenant
     *
     * @param  array  $data  Tenant data including name, slug, domain, etc.
     * @return Tenant Created tenant instance
     *
     * @throws BusinessRuleException When domain or slug is not available
     */
    public function createTenant(array $data): Tenant
    {
        if (isset($data['domain']) && ! $this->tenantRepository->isDomainAvailable($data['domain'])) {
            throw new BusinessRuleException("Tenant with domain '{$data['domain']}' already exists.");
        }

        if (isset($data['slug']) && ! $this->tenantRepository->isSlugAvailable($data['slug'])) {
            throw new BusinessRuleException("Tenant with slug '{$data['slug']}' already exists.");
        }

        $tenant = TransactionHelper::execute(function () use ($data) {
            $tenantData = [
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'domain' => $data['domain'] ?? null,
                'settings' => $data['settings'] ?? [],
                'is_active' => $data['is_active'] ?? true,
            ];

            $tenant = $this->tenantRepository->create($tenantData);

            // Initialize tenant with default configurations
            $this->initializeTenant($tenant);

            return $tenant;
        });

        $this->auditService->logEvent(
            'tenant.created',
            Tenant::class,
            $tenant->id,
            ['name' => $tenant->name, 'slug' => $tenant->slug, 'domain' => $tenant->domain]
        );

        return $tenant->fresh();
    }

    /**
     * Update an existing tenant
     *
     * @param  string  $tenantId  Tenant ID
     * @param  array  $data  Tenant data to update
     * @return Tenant Updated tenant instance
     *
     * @throws TenantNotFoundException When tenant is not found
     * @throws BusinessRuleException When domain or slug is not available
     */
    public function updateTenant(string $tenantId, array $data): Tenant
    {
        $tenant = $this->tenantRepository->find($tenantId);

        if (! $tenant) {
            throw new TenantNotFoundException("Tenant with ID {$tenantId} not found.");
        }

        if (isset($data['domain']) && ! $this->tenantRepository->isDomainAvailable($data['domain'], $tenantId)) {
            throw new BusinessRuleException("Tenant with domain '{$data['domain']}' already exists.");
        }

        if (isset($data['slug']) && ! $this->tenantRepository->isSlugAvailable($data['slug'], $tenantId)) {
            throw new BusinessRuleException("Tenant with slug '{$data['slug']}' already exists.");
        }

        TransactionHelper::execute(function () use ($tenant, $data) {
            $updateData = [];

            foreach (self::ALLOWED_UPDATE_FIELDS as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (! empty($updateData)) {
                $this->tenantRepository->update($tenant->id, $updateData);
            }
        });

        $this->auditService->logEvent(
            'tenant.updated',
            Tenant::class,
            $tenant->id,
            [
                'updated_fields' => array_keys(
                    array_intersect_key($data, array_flip(self::ALLOWED_UPDATE_FIELDS))
                ),
            ]
        );

        return $tenant->fresh();
    }

    /**
     * Delete a tenant (soft delete)
     *
     * @param  string  $tenantId  Tenant ID
     * @return void
     *
     * @throws TenantNotFoundException When tenant is not found
     * @throws BusinessRuleException When tenant has active organizations
     */
    public function deleteTenant(string $tenantId): void
    {
        $tenant = $this->tenantRepository->find($tenantId);

        if (! $tenant) {
            throw new TenantNotFoundException("Tenant with ID {$tenantId} not found.");
        }

        // Check if tenant has active organizations
        $organizationCount = $this->tenantRepository->countOrganizations($tenantId);
        if ($organizationCount > 0) {
            throw new BusinessRuleException(
                "Cannot delete tenant. It has {$organizationCount} organization(s). Please remove or deactivate them first."
            );
        }

        $tenantName = $tenant->name;

        TransactionHelper::execute(function () use ($tenant) {
            $this->tenantRepository->delete($tenant->id);
        });

        $this->auditService->logEvent(
            'tenant.deleted',
            Tenant::class,
            $tenantId,
            ['name' => $tenantName]
        );
    }

    /**
     * Restore a soft-deleted tenant
     *
     * @param  string  $tenantId  Tenant ID
     * @return Tenant Restored tenant instance
     *
     * @throws TenantNotFoundException When tenant is not found
     * @throws BusinessRuleException When tenant is not soft-deleted
     */
    public function restoreTenant(string $tenantId): Tenant
    {
        $tenant = $this->tenantRepository->findWithTrashed($tenantId);

        if (! $tenant) {
            throw new TenantNotFoundException("Tenant with ID {$tenantId} not found.");
        }

        if (! $tenant->trashed()) {
            throw new BusinessRuleException('Tenant is not deleted and cannot be restored.');
        }

        TransactionHelper::execute(function () use ($tenantId) {
            $this->tenantRepository->restore($tenantId);
        });

        $this->auditService->logEvent(
            'tenant.restored',
            Tenant::class,
            $tenant->id,
            ['name' => $tenant->name]
        );

        return $this->tenantRepository->find($tenant->id);
    }

    /**
     * Initialize tenant with default configurations
     *
     * This method handles tenant provisioning logic such as:
     * - Setting up default settings
     * - Creating default resources
     * - Initializing tenant-specific configurations
     *
     * @param  Tenant  $tenant  Tenant instance
     * @return void
     */
    protected function initializeTenant(Tenant $tenant): void
    {
        // Default settings initialization
        $defaultSettings = [
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ];

        // Merge with existing settings if any
        $settings = array_merge($defaultSettings, $tenant->settings ?? []);

        $this->tenantRepository->updateSettings($tenant->id, $settings);
    }
}
