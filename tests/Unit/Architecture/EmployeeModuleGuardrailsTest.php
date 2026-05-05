<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class EmployeeModuleGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_employee_module_provider_is_registered(): void
    {
        $providersFile = $this->readSource('bootstrap/providers.php');

        $this->assertStringContainsString(
            'Modules\\Employee\\Infrastructure\\Providers\\EmployeeServiceProvider',
            $providersFile
        );
    }

    public function test_employee_module_exposes_api_resource_routes(): void
    {
        $routesFile = $this->readSource('app/Modules/Employee/routes/api.php');

        $this->assertStringContainsString('Route::apiResource(\'employees\'', $routesFile);
        $this->assertStringContainsString('auth:api', $routesFile);
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
