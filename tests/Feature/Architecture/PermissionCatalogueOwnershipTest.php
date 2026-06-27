<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Tenant\Services\Plans\TenantModuleCatalogue;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

final class PermissionCatalogueOwnershipTest extends TestCase
{
    public function test_module_seeders_do_not_write_the_tenant_permission_catalogue(): void
    {
        foreach ($this->phpFiles(base_path('app/Modules')) as $file) {
            $path = $file->getPathname();
            if (! str_contains($path, DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'Seeders'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $source = (string) file_get_contents($path);
            self::assertDoesNotMatchRegularExpression(
                "/(?:DB::)?table\\(['\"]permissions['\"]\\)|PermissionModel::query\\(/",
                $source,
                $path.' must not write the User-owned tenant permission catalogue.',
            );
        }
    }

    public function test_route_permission_and_entitlement_guards_use_registered_catalogues(): void
    {
        $permissions = array_keys(app(PermissionDefinitionRegistryInterface::class)->all());
        $modules = app(TenantModuleCatalogue::class)->allCodes();

        foreach ($this->routeFiles() as $file) {
            $source = (string) file_get_contents($file->getPathname());

            preg_match_all('/tenant\.permission:([a-z0-9_.-]+)/', $source, $permissionMatches);
            foreach ($permissionMatches[1] ?? [] as $permission) {
                self::assertContains(
                    $permission,
                    $permissions,
                    $file->getPathname()." references unregistered permission [{$permission}].",
                );
            }

            preg_match_all('/tenant\.feature:([a-z0-9-]+)/', $source, $moduleMatches);
            foreach ($moduleMatches[1] ?? [] as $module) {
                self::assertContains(
                    $module,
                    $modules,
                    $file->getPathname()." references unknown tenant module [{$module}].",
                );
            }
        }
    }

    /** @return list<SplFileInfo> */
    private function routeFiles(): array
    {
        return array_values(array_filter(
            $this->phpFiles(base_path('app/Modules')),
            static fn (SplFileInfo $file): bool => str_ends_with(
                $file->getPathname(),
                DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php',
            ),
        ));
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }
}
