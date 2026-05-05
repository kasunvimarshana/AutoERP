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

    public function test_shared_module_top_level_structure_remains_minimal(): void
    {
        $sharedRoot = $this->repoRoot.'/app/Modules/Shared';
        $entries = array_values(array_filter(scandir($sharedRoot) ?: [], static function (string $entry): bool {
            return $entry !== '.' && $entry !== '..';
        }));

        sort($entries);

        $this->assertSame(
            ['Infrastructure', 'routes'],
            $entries,
            'Shared module should remain a thin shell with only Infrastructure and routes directories.'
        );
    }

    public function test_shared_module_keeps_no_runtime_migrations(): void
    {
        $migrationDir = $this->repoRoot.'/app/Modules/Shared/database/migrations';
        if (! is_dir($migrationDir)) {
            $this->assertSame([], [], 'Shared module should remain minimal and avoid domain-owned runtime migrations.');

            return;
        }

        $entries = array_values(array_filter(scandir($migrationDir) ?: [], static function (string $entry): bool {
            return $entry !== '.' && $entry !== '..';
        }));

        sort($entries);

        $this->assertSame([], $entries, 'Shared module should remain minimal and avoid domain-owned runtime migrations.');
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
