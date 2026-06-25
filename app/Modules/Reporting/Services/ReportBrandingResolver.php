<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class ReportBrandingResolver
{
    public function __construct(private readonly ConfigurationResolverInterface $configuration) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(int $tenantId, ?int $organizationUnitId): array
    {
        $tenant = TenantModel::query()->with('baseCurrency')->findOrFail($tenantId);
        $organizationUnit = $organizationUnitId === null
            ? null
            : OrganizationUnitModel::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($organizationUnitId);

        return [
            'company_name' => (string) $this->configuration->value('branding.display_name', $tenantId, $organizationUnitId),
            'tenant_name' => (string) $tenant->name,
            'tenant_code' => (string) $tenant->code,
            'organization_unit_name' => $organizationUnit?->name,
            'organization_unit_code' => $organizationUnit?->code,
            'currency_code' => $tenant->baseCurrency?->code,
            'logo_data_uri' => $this->imageDataUri(
                (string) ($organizationUnit?->image_path ?: $tenant->logo_path ?: ''),
            ),
        ];
    }

    private function imageDataUri(string $path): ?string
    {
        if ($path === '' || str_contains($path, '://')) {
            return null;
        }

        $candidates = [
            public_path(ltrim($path, '/\\')),
            storage_path('app/'.ltrim($path, '/\\')),
            storage_path('app/public/'.ltrim($path, '/\\')),
        ];
        $allowedRoots = array_filter([
            realpath(public_path()),
            realpath(storage_path('app')),
        ]);

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath === false || ! is_file($realPath) || ! is_readable($realPath)) {
                continue;
            }

            $insideAllowedRoot = false;
            foreach ($allowedRoots as $root) {
                if (str_starts_with(strtolower($realPath), strtolower($root.DIRECTORY_SEPARATOR))) {
                    $insideAllowedRoot = true;
                    break;
                }
            }

            if (! $insideAllowedRoot) {
                continue;
            }

            $mime = mime_content_type($realPath);
            if (! is_string($mime) || ! str_starts_with($mime, 'image/')) {
                continue;
            }

            $content = file_get_contents($realPath);

            return $content === false ? null : 'data:'.$mime.';base64,'.base64_encode($content);
        }

        return null;
    }
}
