<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Models\CurrencyModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

trait ResolvesSeedContext
{
    private function defaultTenant(): ?TenantModel
    {
        if (! Schema::hasTable('tenants')) {
            return null;
        }

        return TenantModel::query()
            ->where('code', $this->defaultTenantCode())
            ->first();
    }

    private function defaultOrganizationUnit(?TenantModel $tenant = null): ?OrganizationUnitModel
    {
        if (! Schema::hasTable('organization_units')) {
            return null;
        }

        $tenant ??= $this->defaultTenant();
        if ($tenant === null) {
            return null;
        }

        return OrganizationUnitModel::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('code', $this->defaultOrganizationUnitCode())
            ->first();
    }

    private function defaultCurrency(): ?CurrencyModel
    {
        if (! Schema::hasTable('currencies')) {
            return null;
        }

        return CurrencyModel::query()
            ->where('code', $this->defaultCurrencyCode())
            ->first();
    }

    private function defaultTenantCode(): string
    {
        return strtoupper(trim((string) config('app.bootstrap.tenant_code', 'AUTOERP')));
    }

    private function defaultOrganizationUnitCode(): string
    {
        return strtoupper(trim((string) config('app.bootstrap.organization_unit_code', 'HQ')));
    }

    private function defaultCurrencyCode(): string
    {
        return strtoupper(trim((string) config('app.bootstrap.currency_code', 'USD')));
    }
}
