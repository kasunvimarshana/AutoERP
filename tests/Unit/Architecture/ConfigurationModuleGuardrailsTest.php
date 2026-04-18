<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class ConfigurationModuleGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_configuration_module_provider_is_registered(): void
    {
        $providersFile = $this->readSource('bootstrap/providers.php');

        $this->assertStringContainsString(
            'Modules\\Configuration\\Infrastructure\\Providers\\ConfigurationServiceProvider',
            $providersFile
        );
    }

    public function test_configuration_module_does_not_keep_redundant_module_configurations_migration(): void
    {
        $migrationDir = $this->repoRoot.'/app/Modules/Configuration/database/migrations';
        $entries = array_values(array_filter(scandir($migrationDir) ?: [], static function (string $entry): bool {
            return $entry !== '.' && $entry !== '..';
        }));

        sort($entries);

        $this->assertSame(
            [],
            $entries,
            'Configuration module must not duplicate tenant_settings persistence logic.'
        );
    }

    public function test_configuration_module_exposes_no_http_endpoints(): void
    {
        $routesFile = $this->readSource('app/Modules/Configuration/routes/api.php');

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
