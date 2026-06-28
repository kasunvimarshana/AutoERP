<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class CoreModuleArchitectureTest extends TestCase
{
    public function test_production_module_dependency_graph_is_acyclic(): void
    {
        $graph = [];
        foreach ($this->productionModuleFiles() as $file) {
            $owner = $this->moduleName($file);
            $graph[$owner] ??= [];
            $source = (string) file_get_contents($file->getPathname());

            preg_match_all('/^use Modules\\\\([A-Za-z0-9_]+)\\\\/m', $source, $matches);
            foreach ($matches[1] as $dependency) {
                if ($dependency !== $owner) {
                    $graph[$owner][$dependency] = true;
                    $graph[$dependency] ??= [];
                }
            }
        }

        self::assertSame([], $this->cyclicComponents($graph));
    }

    public function test_removed_generic_and_duplicate_ownership_sources_do_not_exist(): void
    {
        self::assertDirectoryDoesNotExist($this->modulesPath('Extension'));

        foreach ([
            $this->modulesPath('Customer/Database/Migrations/2026_06_12_120010_create_customer_vehicles_table.php'),
            $this->modulesPath('Supplier/Database/Migrations/2026_06_12_120011_create_supplier_vehicles_table.php'),
        ] as $legacyMigration) {
            self::assertFileDoesNotExist($legacyMigration);
        }

        $vehicleOwnership = $this->source('Vehicle/Database/Migrations/2026_06_12_120007_create_vehicle_ownerships_table.php');
        self::assertStringContainsString("Schema::create('vehicle_ownerships'", $vehicleOwnership);
        self::assertStringContainsString("'owner_type'", $vehicleOwnership);
        self::assertStringContainsString("'owner_code_snapshot'", $vehicleOwnership);
        self::assertStringContainsString("'owner_name_snapshot'", $vehicleOwnership);
    }

    public function test_corrected_owner_boundaries_do_not_regress_to_concrete_dependencies(): void
    {
        $itemSources = $this->productionSources('Item');
        self::assertStringNotContainsString('use Modules\\Inventory\\', $itemSources);

        $invoiceRestoration = $this->source('Invoice/Services/InvoiceSourceRestorationService.php');
        foreach (['Purchase', 'Sales', 'VehicleService'] as $module) {
            self::assertStringNotContainsString("use Modules\\{$module}\\", $invoiceRestoration);
        }
        self::assertStringContainsString('InvoiceSourceCancellationRegistry', $invoiceRestoration);

        $taxSources = $this->productionSources('Tax');
        foreach (['Customer', 'Supplier', 'Item', 'Invoice', 'Payment', 'Purchase', 'Sales'] as $module) {
            self::assertStringNotContainsString("use Modules\\{$module}\\", $taxSources);
        }
    }

    public function test_user_module_is_the_only_production_permission_catalogue_writer(): void
    {
        $violations = [];
        foreach ($this->productionModuleFiles() as $file) {
            if ($this->moduleName($file) === 'User') {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            if (preg_match('/DB::table\([\'\"]permissions[\'\"]\)/', $source) === 1) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertSame([], $violations);
        self::assertFileExists($this->modulesPath('User/Database/Seeders/TenantPermissionSeeder.php'));
    }

    public function test_module_namespaces_match_their_owner_directories(): void
    {
        $violations = [];
        foreach ($this->moduleFiles() as $file) {
            $source = (string) file_get_contents($file->getPathname());
            if (preg_match('/^namespace\s+([^;]+);/m', $source, $match) !== 1) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($this->modulesPath(''))));
            $expectedDirectory = trim(str_replace('/', '\\', dirname($relative)), '.\\');
            $expectedNamespace = 'Modules\\'.$expectedDirectory;
            if ($match[1] !== $expectedNamespace) {
                $violations[] = [
                    'file' => $relative,
                    'expected' => $expectedNamespace,
                    'actual' => $match[1],
                ];
            }
        }

        self::assertSame([], $violations);
    }

    public function test_core_does_not_reclaim_feature_owned_storage_password_or_idempotency(): void
    {
        foreach ([
            'Contracts/FileStorageServiceInterface.php',
            'Contracts/PasswordHasherInterface.php',
            'Models/IdempotencyRecord.php',
            'Services/FileStorageService.php',
            'Services/IdempotencyService.php',
            'Services/PasswordHasher.php',
        ] as $legacyPath) {
            self::assertFileDoesNotExist($this->modulesPath('Core/'.$legacyPath));
        }

        self::assertFileExists($this->modulesPath('PrivateObject/Contracts/PrivateObjectStorageInterface.php'));
        self::assertFileExists($this->modulesPath('Idempotency/Models/IdempotencyRecord.php'));
        self::assertFileExists($this->modulesPath('Auth/Contracts/PasswordHasherInterface.php'));
    }

    /** @return list<SplFileInfo> */
    private function productionModuleFiles(): array
    {
        return array_values(array_filter(
            $this->moduleFiles(),
            static fn (SplFileInfo $file): bool => ! str_contains(
                $file->getPathname(),
                DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR,
            ),
        ));
    }

    /** @return list<SplFileInfo> */
    private function moduleFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->modulesPath('')));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function moduleName(SplFileInfo $file): string
    {
        $relative = substr($file->getPathname(), strlen($this->modulesPath('')));

        return explode(DIRECTORY_SEPARATOR, ltrim($relative, DIRECTORY_SEPARATOR))[0];
    }

    private function productionSources(string $module): string
    {
        $sources = '';
        foreach ($this->productionModuleFiles() as $file) {
            if ($this->moduleName($file) === $module) {
                $sources .= (string) file_get_contents($file->getPathname());
            }
        }

        return $sources;
    }

    /**
     * @param  array<string,array<string,bool>>  $graph
     * @return list<list<string>>
     */
    private function cyclicComponents(array $graph): array
    {
        $index = 0;
        $indices = [];
        $lowLinks = [];
        $stack = [];
        $onStack = [];
        $cycles = [];

        $visit = function (string $node) use (&$visit, &$graph, &$index, &$indices, &$lowLinks, &$stack, &$onStack, &$cycles): void {
            $indices[$node] = $index;
            $lowLinks[$node] = $index;
            $index++;
            $stack[] = $node;
            $onStack[$node] = true;

            foreach (array_keys($graph[$node] ?? []) as $dependency) {
                if (! array_key_exists($dependency, $indices)) {
                    $visit($dependency);
                    $lowLinks[$node] = min($lowLinks[$node], $lowLinks[$dependency]);
                } elseif ($onStack[$dependency] ?? false) {
                    $lowLinks[$node] = min($lowLinks[$node], $indices[$dependency]);
                }
            }

            if ($lowLinks[$node] !== $indices[$node]) {
                return;
            }

            $component = [];
            do {
                $member = array_pop($stack);
                self::assertIsString($member);
                $onStack[$member] = false;
                $component[] = $member;
            } while ($member !== $node);

            if (count($component) > 1 || isset($graph[$node][$node])) {
                sort($component);
                $cycles[] = $component;
            }
        };

        foreach (array_keys($graph) as $node) {
            if (! array_key_exists($node, $indices)) {
                $visit($node);
            }
        }

        sort($cycles);

        return $cycles;
    }

    private function source(string $relativePath): string
    {
        $path = $this->modulesPath($relativePath);
        $source = file_get_contents($path);
        self::assertNotFalse($source, $path);

        return $source;
    }

    private function modulesPath(string $relativePath): string
    {
        $base = dirname(__DIR__, 3).'/app/Modules';

        return $relativePath === '' ? $base.DIRECTORY_SEPARATOR : $base.'/'.$relativePath;
    }
}
