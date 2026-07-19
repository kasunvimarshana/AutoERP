<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use Modules\Core\Tenancy\TenantFeature;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ModuleApiVersioningTest extends TestCase
{
    public function test_module_api_route_prefixes_are_versioned(): void
    {
        $moduleDirectory = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleDirectory));
        $routeFiles = [];

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if (str_ends_with($file->getPathname(), DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php')) {
                $routeFiles[] = $file->getPathname();
            }
        }

        self::assertNotSame([], $routeFiles, 'No module API route files were discovered.');

        foreach ($routeFiles as $routeFile) {
            $source = (string) file_get_contents($routeFile);
            preg_match_all('/Route::prefix\([\'\"](api\/[^\'\"]+)[\'\"]\)/', $source, $matches);

            foreach ($matches[1] ?? [] as $prefix) {
                self::assertStringStartsWith(
                    'api/v1',
                    $prefix,
                    $routeFile.' contains an unversioned application API prefix: '.$prefix,
                );
            }
        }
    }

    public function test_plan_controlled_module_api_routes_declare_tenant_feature_gate(): void
    {
        foreach ($this->planControlledRouteFeatureGates() as $relativePath => $gate) {
            $routeFile = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            self::assertFileExists($routeFile, "Expected plan-controlled route file [{$relativePath}] to exist.");

            $source = (string) file_get_contents($routeFile);

            self::assertTrue(
                $this->sourceDeclaresTenantFeatureGate($source, $gate['feature'], $gate['constant']),
                "Route file [{$relativePath}] must declare tenant feature gate [{$gate['feature']}].",
            );
        }
    }

    /** @return array<string,array{feature:string,constant:string}> */
    private function planControlledRouteFeatureGates(): array
    {
        return [
            'app/Modules/Customer/Routes/api.php' => ['feature' => TenantFeature::CUSTOMER, 'constant' => 'CUSTOMER'],
            'app/Modules/Supplier/Routes/api.php' => ['feature' => TenantFeature::SUPPLIER, 'constant' => 'SUPPLIER'],
            'app/Modules/Hr/Routes/api.php' => ['feature' => TenantFeature::HR, 'constant' => 'HR'],
            'app/Modules/Item/Routes/api.php' => ['feature' => TenantFeature::ITEM, 'constant' => 'ITEM'],
            'app/Modules/Warehouse/Routes/api.php' => ['feature' => TenantFeature::WAREHOUSE, 'constant' => 'WAREHOUSE'],
            'app/Modules/Inventory/Routes/api.php' => ['feature' => TenantFeature::INVENTORY, 'constant' => 'INVENTORY'],
            'app/Modules/Purchase/Routes/api.php' => ['feature' => TenantFeature::PURCHASE, 'constant' => 'PURCHASE'],
            'app/Modules/Vehicle/Routes/api.php' => ['feature' => TenantFeature::VEHICLE, 'constant' => 'VEHICLE'],
            'app/Modules/VehicleService/Routes/api.php' => ['feature' => TenantFeature::VEHICLE_SERVICE, 'constant' => 'VEHICLE_SERVICE'],
            'app/Modules/VehicleRental/Routes/api.php' => ['feature' => TenantFeature::VEHICLE_RENTAL, 'constant' => 'VEHICLE_RENTAL'],
            'app/Modules/Invoice/Routes/api.php' => ['feature' => TenantFeature::INVOICE, 'constant' => 'INVOICE'],
            'app/Modules/Payment/Routes/api.php' => ['feature' => TenantFeature::PAYMENT, 'constant' => 'PAYMENT'],
            'app/Modules/Finance/Routes/api.php' => ['feature' => TenantFeature::FINANCE, 'constant' => 'FINANCE'],
            'app/Modules/Tax/Routes/api.php' => ['feature' => TenantFeature::FINANCE, 'constant' => 'FINANCE'],
            'app/Modules/Reporting/Routes/api.php' => ['feature' => TenantFeature::REPORTING, 'constant' => 'REPORTING'],
        ];
    }

    private function sourceDeclaresTenantFeatureGate(string $source, string $feature, string $constant): bool
    {
        $literalMiddleware = 'tenant.feature:'.$feature;

        return str_contains($source, "'{$literalMiddleware}'")
            || str_contains($source, '"'.$literalMiddleware.'"')
            || str_contains($source, 'TenantFeature::'.$constant);
    }
}
