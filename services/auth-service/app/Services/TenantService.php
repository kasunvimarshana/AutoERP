<?php

namespace App\Services;

use App\Domain\Contracts\TenantRepositoryInterface;
use App\Domain\Contracts\TenantServiceInterface;
use App\Domain\Events\TenantCreated;
use App\Domain\Models\AuditLog;
use App\Domain\Models\Tenant;
use App\Domain\Models\User;
use App\Infrastructure\Tenant\TenantDatabaseManager;
use App\Infrastructure\Tenant\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TenantService implements TenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TenantDatabaseManager $dbManager,
        private readonly TenantResolver $resolver,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function list(array $params = []): LengthAwarePaginator|Collection
    {
        return $this->tenantRepository->findAll($params);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Tenant
    {
        DB::beginTransaction();

        try {
            // Create the tenant record
            $tenant = $this->tenantRepository->create([
                'name'      => $data['name'],
                'subdomain' => $data['subdomain'],
                'plan'      => $data['plan'],
                'status'    => 'active',
                'settings'  => $data['settings'] ?? [],
                'features'  => array_merge(
                    config('tenant.default_features', []),
                    $data['features'] ?? []
                ),
                'config'    => $data['config'] ?? [],
            ]);

            // Provision the tenant's database schema
            $this->provisionDatabase($tenant);

            // Create the tenant admin user
            $adminUser = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['admin_name'],
                'email'     => $data['admin_email'],
                'password'  => Hash::make($data['admin_password']),
                'is_active' => true,
            ]);

            $adminUser->assignRole('tenant-admin');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Tenant creation failed', [
                'error'    => $e->getMessage(),
                'subdomain'=> $data['subdomain'] ?? null,
            ]);
            throw $e;
        }

        Event::dispatch(new TenantCreated(tenant: $tenant, adminUserId: $adminUser->id));

        AuditLog::record(
            event: 'tenant.created',
            userId: auth()->id(),
            tenantId: $tenant->id,
            auditable: $tenant,
            newValues: ['name' => $tenant->name, 'subdomain' => $tenant->subdomain, 'plan' => $tenant->plan],
        );

        return $tenant->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $tenantId, array $data): Tenant
    {
        $tenant    = $this->tenantRepository->findOrFail($tenantId);
        $oldValues = $tenant->toArray();

        $updated = $this->tenantRepository->update($tenantId, $data);

        AuditLog::record(
            event: 'tenant.updated',
            userId: auth()->id(),
            tenantId: $tenantId,
            auditable: $updated,
            oldValues: $oldValues,
            newValues: $data,
        );

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $tenantId): bool
    {
        $tenant = $this->tenantRepository->findOrFail($tenantId);

        AuditLog::record(
            event: 'tenant.deleted',
            userId: auth()->id(),
            tenantId: $tenantId,
            auditable: $tenant,
            oldValues: $tenant->toArray(),
        );

        return $this->tenantRepository->delete($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(string $tenantId): Tenant
    {
        return $this->tenantRepository->findOrFail($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return $this->tenantRepository->findBySubdomain($subdomain);
    }

    /**
     * {@inheritdoc}
     */
    public function switchTenant(User $user, string $targetTenantId, ?string $deviceId = null): array
    {
        $targetTenant = $this->tenantRepository->findOrFail($targetTenantId);

        if ($targetTenant->status !== 'active') {
            throw new \RuntimeException('Target tenant is not active.');
        }

        // Ensure the user has access to the target tenant
        // Super-admins can switch to any tenant; regular users only to their own
        if (!$user->hasRole('super-admin') && $user->tenant_id !== $targetTenantId) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'You do not have access to this tenant.'
            );
        }

        // Revoke current token
        $user->token()?->revoke();

        // Temporarily update user's tenant context
        $user->tenant_id = $targetTenantId;

        // Issue new token with new tenant claims
        $tokenResult = $this->tokenService->createForUser(
            $user,
            $deviceId ? "device:{$deviceId}" : 'tenant-switch-token',
            ['*'],
            ['tenant_id' => $targetTenant->id, 'tenant_slug' => $targetTenant->subdomain]
        );

        AuditLog::record(
            event: 'user.tenant_switched',
            userId: $user->id,
            tenantId: $targetTenantId,
            auditable: $user,
            metadata: ['target_tenant' => $targetTenantId, 'device_id' => $deviceId],
        );

        return array_merge($tokenResult, ['tenant' => $targetTenant]);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveFromRequest(Request $request): ?Tenant
    {
        return $this->resolver->resolve($request);
    }

    /**
     * {@inheritdoc}
     */
    public function provisionDatabase(Tenant $tenant): void
    {
        $this->dbManager->createSchemaForTenant($tenant);
        Log::info("Provisioned database schema for tenant: {$tenant->subdomain}");
    }
}
