<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Tenant;

class TenantService extends BaseService
{
    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    public function createTenant(array $data): Tenant
    {
        return $this->transaction(function () use ($data) {
            $tenant = Tenant::create($data);

            $this->createTenantDatabase($tenant);
            $this->runTenantMigrations($tenant);

            $this->dispatchEvent(new \Modules\Core\Events\TenantCreated($tenant));

            return $tenant;
        });
    }

    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        return $this->transaction(function () use ($tenant, $data) {
            $tenant->update($data);

            $this->dispatchEvent(new \Modules\Core\Events\TenantUpdated($tenant));

            return $tenant;
        });
    }

    public function deleteTenant(Tenant $tenant): bool
    {
        return $this->transaction(function () use ($tenant) {
            $this->dispatchEvent(new \Modules\Core\Events\TenantDeleting($tenant));

            $result = $tenant->delete();

            $this->dispatchEvent(new \Modules\Core\Events\TenantDeleted($tenant));

            return $result;
        });
    }

    public function suspendTenant(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => 'suspended']);

        $this->dispatchEvent(new \Modules\Core\Events\TenantSuspended($tenant));

        return $tenant;
    }

    public function activateTenant(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => 'active']);

        $this->dispatchEvent(new \Modules\Core\Events\TenantActivated($tenant));

        return $tenant;
    }

    protected function createTenantDatabase(Tenant $tenant): void
    {
        $database = $tenant->getDatabaseConnection();

        // Validate database name to prevent SQL injection
        // Only allow alphanumeric and underscore, max 64 chars (database platform limit)
        if (! preg_match('/^[a-zA-Z0-9_]{1,64}$/', $database)) {
            throw new \InvalidArgumentException('Invalid database name format');
        }

        // Use Doctrine's platform-agnostic identifier quoting for security
        // This works across PostgreSQL, MySQL, SQLite, and other supported databases
        $quotedDatabase = DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->quoteIdentifier($database);
        
        DB::statement("CREATE DATABASE {$quotedDatabase}");
    }

    protected function runTenantMigrations(Tenant $tenant): void
    {
        $this->tenantContext->runForTenant($tenant, function () {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'modules/Core/database/migrations/tenant',
                '--force' => true,
            ]);
        });
    }

    public function getTenantByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    public function getTenantByUuid(string $uuid): ?Tenant
    {
        return Tenant::where('uuid', $uuid)->first();
    }
}
