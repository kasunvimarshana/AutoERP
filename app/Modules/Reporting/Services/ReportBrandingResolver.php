<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitBrandingReaderInterface;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Services\Contracts\TenantBrandingAssetReaderInterface;

final class ReportBrandingResolver
{
    public function __construct(
        private readonly ConfigurationResolverInterface $configuration,
        private readonly TenantBrandingAssetReaderInterface $tenantBrandingAssets,
        private readonly OrganizationUnitBrandingReaderInterface $organizationUnitBranding,
    ) {}

    /** @return array<string, mixed> */
    public function resolve(int $tenantId, ?int $organizationUnitId): array
    {
        $tenant = TenantModel::query()->with('baseCurrency')->findOrFail($tenantId);
        $organizationUnit = $organizationUnitId === null
            ? null
            : $this->organizationUnitBranding->read($tenantId, $organizationUnitId);

        return [
            'company_name' => (string) $this->configuration->value(
                'branding.display_name',
                $tenantId,
                $organizationUnitId,
            ),
            'tenant_name' => (string) $tenant->name,
            'tenant_code' => (string) $tenant->code,
            'organization_unit_name' => $organizationUnit?->name,
            'organization_unit_code' => $organizationUnit?->code,
            'currency_code' => $tenant->baseCurrency?->code,
            'logo_data_uri' => $organizationUnit?->logoDataUri
                ?? $this->tenantBrandingAssets->logoDataUri(
                    $tenantId,
                    is_string($tenant->logo_object_key) ? $tenant->logo_object_key : null,
                    is_string($tenant->logo_mime_type) ? $tenant->logo_mime_type : null,
                ),
        ];
    }
}
