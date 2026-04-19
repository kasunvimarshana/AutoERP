<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class SharedModuleGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_shared_module_provider_is_registered(): void
    {
        $providersFile = $this->readSource('bootstrap/providers.php');

        $this->assertStringContainsString(
            'Modules\\Shared\\Infrastructure\\Providers\\SharedServiceProvider',
            $providersFile
        );
    }

    public function test_shared_module_only_keeps_global_reference_migration(): void
    {
        $migrationDir = $this->repoRoot.'/app/Modules/Shared/database/migrations';
        $entries = array_values(array_filter(scandir($migrationDir) ?: [], static function (string $entry): bool {
            return $entry !== '.' && $entry !== '..';
        }));

        sort($entries);

        $this->assertSame(
            [
                '2024_01_01_000002a_create_countries_table.php',
                '2024_01_01_000002b_create_currencies_table.php',
                '2024_01_01_000002c_create_languages_table.php',
                '2024_01_01_000002d_create_timezones_table.php',
            ],
            $entries,
            'Shared module must keep only globally reusable reference-table migrations.'
        );
    }

    public function test_shared_module_does_not_define_http_endpoints(): void
    {
        $routesFile = $this->readSource('app/Modules/Shared/routes/api.php');

        $this->assertStringNotContainsString('Route::', $routesFile);
    }

    private function readSource(string $relativePath): string
    {
        $fullPath = $this->repoRoot.'/'.$relativePath;
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            $this->fail('Unable to read source file: '.$relativePath);
        }

        return $contents;
    }
}
