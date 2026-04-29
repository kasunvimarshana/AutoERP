<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class FinanceModuleGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_finance_module_provider_is_registered(): void
    {
        $providersFile = $this->readSource('bootstrap/providers.php');

        $this->assertStringContainsString(
            'Modules\\Finance\\Infrastructure\\Providers\\FinanceServiceProvider',
            $providersFile
        );
    }

    public function test_finance_module_exposes_api_resource_routes(): void
    {
        $routesFile = $this->readSource('app/Modules/Finance/routes/api.php');

        $this->assertStringContainsString('Route::apiResource(\'accounts\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'fiscal-years\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'fiscal-periods\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'journal-entries\'', $routesFile);
        $this->assertStringContainsString('journal-entries/{journal_entry}/post', $routesFile);
        $this->assertStringContainsString('auth.configured', $routesFile);
        $this->assertStringContainsString('resolve.tenant', $routesFile);
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
