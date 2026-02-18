<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Tenant;

class TenantContext
{
    protected ?Tenant $tenant = null;

    protected ?int $tenantId = null;

    protected bool $resolved = false;

    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->tenantId = $tenant?->id;
        $this->resolved = true;

        if ($tenant) {
            $this->switchDatabase($tenant);
        }
    }

    public function setTenantById(?int $tenantId): void
    {
        $this->tenantId = $tenantId;

        if ($tenantId) {
            $tenant = $this->resolveTenant($tenantId);
            $this->setTenant($tenant);
        } else {
            $this->tenant = null;
            $this->resolved = true;
        }
    }

    public function getTenant(): ?Tenant
    {
        if (! $this->resolved && $this->tenantId) {
            $this->tenant = $this->resolveTenant($this->tenantId);
            $this->resolved = true;
        }

        return $this->tenant;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->tenantId = null;
        $this->resolved = false;
    }

    protected function resolveTenant(int $tenantId): ?Tenant
    {
        return Cache::remember(
            "tenant:{$tenantId}",
            3600,
            fn () => Tenant::find($tenantId)
        );
    }

    protected function switchDatabase(Tenant $tenant): void
    {
        $database = $tenant->getDatabaseConnection();

        Config::set('database.connections.tenant', [
            'driver' => 'pgsql',
            'host' => config('database.connections.pgsql.host'),
            'port' => config('database.connections.pgsql.port'),
            'database' => $database,
            'username' => config('database.connections.pgsql.username'),
            'password' => config('database.connections.pgsql.password'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function getTenantConnection(): string
    {
        return $this->hasTenant() ? 'tenant' : 'pgsql';
    }

    public function runForTenant(Tenant $tenant, callable $callback)
    {
        $originalTenant = $this->tenant;
        $originalTenantId = $this->tenantId;

        try {
            $this->setTenant($tenant);

            return $callback();
        } finally {
            if ($originalTenant) {
                $this->setTenant($originalTenant);
            } else {
                $this->clear();
            }
        }
    }
}
