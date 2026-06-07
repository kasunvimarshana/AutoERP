<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders\Concerns;

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
        return strtoupper(trim((string) env('AUTOERP_TENANT_CODE', 'AUTOERP')));
    }

    private function defaultOrganizationUnitCode(): string
    {
        return strtoupper(trim((string) env('AUTOERP_ORGANIZATION_UNIT_CODE', 'HQ')));
    }

    private function defaultCurrencyCode(): string
    {
        return strtoupper(trim((string) env('AUTOERP_CURRENCY_CODE', 'USD')));
    }
}
