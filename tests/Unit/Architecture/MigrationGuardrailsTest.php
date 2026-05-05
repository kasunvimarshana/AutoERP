<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class MigrationGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_migrations_do_not_define_duplicate_foreign_keys_for_already_constrained_columns(): void
    {
        $violations = [];

        foreach ($this->moduleMigrationFiles() as $filePath) {
            $contents = file_get_contents($filePath);
            if ($contents === false) {
                $this->fail('Unable to read migration file: '.$filePath);
            }

            preg_match_all(
                '/foreignId\(\s*[\'\"]([a-zA-Z0-9_]+)[\'\"]\s*\)[^;\n]*->constrained\(/',
                $contents,
                $matches
            );

            $constrainedColumns = array_values(array_unique($matches[1] ?? []));
            foreach ($constrainedColumns as $column) {
                $manualForeignPattern = '/foreign\(\s*[\'\"]'.preg_quote($column, '/').'[\'\"]\s*\)->references\(/';
                if (preg_match($manualForeignPattern, $contents) === 1) {
                    $violations[] = str_replace('\\', '/', $filePath).' -> '.$column;
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Migrations must not duplicate foreign key definitions for already constrained foreignId columns.\n".implode("\n", $violations)
        );
    }

    public function test_workflow_migrations_include_status_operational_indexes(): void
    {
        $requiredIndexChecks = [
            'app/Modules/Sales/database/migrations/2024_01_01_110001_create_sales_orders_table.php' => 'sales_orders_tenant_customer_status_idx',
            'app/Modules/Purchase/database/migrations/2024_01_01_100001_create_purchase_orders_table.php' => 'purchase_orders_tenant_supplier_status_idx',
            'app/Modules/Finance/database/migrations/2024_01_01_120005b_create_payments_table.php' => 'payments_tenant_status_date_idx',
            'app/Modules/HR/database/migrations/2024_01_01_900006_create_hr_leave_requests_table.php' => 'hr_leave_requests_tenant_status_start_date_idx',
            'app/Modules/HR/database/migrations/2024_01_01_900010_create_hr_payroll_runs_table.php' => 'hr_payroll_runs_tenant_status_period_end_idx',
            'app/Modules/HR/database/migrations/2024_01_01_900012_create_hr_payslips_table.php' => 'hr_payslips_tenant_status_run_idx',
            'app/Modules/HR/database/migrations/2024_01_01_900015_create_hr_performance_reviews_table.php' => 'hr_performance_reviews_tenant_status_cycle_idx',
        ];

        foreach ($requiredIndexChecks as $relativePath => $indexName) {
            $contents = $this->readSource($relativePath);
            $this->assertStringContainsString(
                $indexName,
                $contents,
                'Missing operational status index in migration: '.$relativePath
            );
        }
    }

    public function test_payment_idempotency_contract_is_wired_across_layers(): void
    {
        $checks = [
            'app/Modules/Finance/database/migrations/2024_01_01_120005b_create_payments_table.php' => [
                'idempotency_key',
                'payments_tenant_idempotency_key_uk',
            ],
            'app/Modules/Finance/Infrastructure/Persistence/Eloquent/Models/PaymentModel.php' => [
                "'idempotency_key'",
            ],
            'app/Modules/Finance/Application/DTOs/PaymentData.php' => [
                'idempotency_key',
            ],
            'app/Modules/Finance/Domain/Entities/Payment.php' => [
                'idempotencyKey',
                'getIdempotencyKey',
            ],
            'app/Modules/Finance/Domain/RepositoryInterfaces/PaymentRepositoryInterface.php' => [
                'findByTenantAndIdempotencyKey',
            ],
            'app/Modules/Finance/Infrastructure/Persistence/Eloquent/Repositories/EloquentPaymentRepository.php' => [
                'findByTenantAndIdempotencyKey',
                "'idempotency_key'",
            ],
            'app/Modules/Finance/Application/Services/CreatePaymentService.php' => [
                'findByTenantAndIdempotencyKey',
                'idempotency_key',
            ],
            'app/Modules/Finance/Infrastructure/Http/Requests/StorePaymentRequest.php' => [
                'idempotency_key',
            ],
            'app/Modules/Finance/Infrastructure/Http/Resources/PaymentResource.php' => [
                'idempotency_key',
                'getIdempotencyKey',
            ],
        ];

        foreach ($checks as $relativePath => $needles) {
            $contents = $this->readSource($relativePath);

            foreach ($needles as $needle) {
                $this->assertStringContainsString(
                    $needle,
                    $contents,
                    'Missing payment idempotency contract fragment in: '.$relativePath.' -> '.$needle
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function moduleMigrationFiles(): array
    {
        $root = $this->repoRoot.'/app/Modules';
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $normalizedPath = str_replace('\\', '/', $fileInfo->getPathname());
            if (! str_contains($normalizedPath, '/database/migrations/')) {
                continue;
            }

            $files[] = $fileInfo->getPathname();
        }

        sort($files);

        return $files;
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
