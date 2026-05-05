<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class HRModuleGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_hr_module_provider_is_registered(): void
    {
        $providersFile = $this->readSource('bootstrap/providers.php');

        $this->assertStringContainsString(
            'Modules\\HR\\Infrastructure\\Providers\\HRServiceProvider',
            $providersFile
        );
    }

    public function test_hr_module_exposes_expected_routes_and_middleware(): void
    {
        $routesFile = $this->readSource('app/Modules/HR/routes/api.php');

        $this->assertStringContainsString("Route::prefix('hr')", $routesFile);

        $this->assertStringContainsString("Route::apiResource('shifts'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('leave-types'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('leave-policies'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('leave-requests'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('biometric-devices'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('payroll-runs'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('payroll-items'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('performance-cycles'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('performance-reviews'", $routesFile);
        $this->assertStringContainsString("Route::apiResource('employee-documents'", $routesFile);

        $this->assertStringContainsString('auth.configured', $routesFile);
        $this->assertStringContainsString('resolve.tenant', $routesFile);
    }

    public function test_hr_module_exposes_expected_action_routes(): void
    {
        $routesFile = $this->readSource('app/Modules/HR/routes/api.php');

        $this->assertStringContainsString("shifts/{shift}/assign", $routesFile);
        $this->assertStringContainsString("leave-requests/{leave_request}/approve", $routesFile);
        $this->assertStringContainsString("leave-requests/{leave_request}/reject", $routesFile);
        $this->assertStringContainsString("leave-requests/{leave_request}/cancel", $routesFile);
        $this->assertStringContainsString("attendance-records/process", $routesFile);
        $this->assertStringContainsString("biometric-devices/{biometric_device}/sync", $routesFile);
        $this->assertStringContainsString("payroll-runs/{payroll_run}/approve", $routesFile);
        $this->assertStringContainsString("payroll-runs/{payroll_run}/process", $routesFile);
        $this->assertStringContainsString("performance-reviews/{performance_review}/submit", $routesFile);
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
