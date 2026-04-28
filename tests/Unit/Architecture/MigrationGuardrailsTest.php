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

    public function test_inventory_seed_migrations_keep_explicit_cross_module_foreign_key_targets(): void
    {
        $batchesMigration = $this->readSource('app/Modules/Inventory/database/migrations/2024_01_01_900001_create_batches_table.php');
        $serialsMigration = $this->readSource('app/Modules/Inventory/database/migrations/2024_01_01_900002_create_serials_table.php');

        $this->assertStringContainsString("constrained('products', 'id', 'batches_product_id_fk')", $batchesMigration);
        $this->assertStringContainsString("constrained('suppliers', 'id', 'batches_supplier_id_fk')", $batchesMigration);

        $this->assertStringContainsString("constrained('products', 'id', 'serials_product_id_fk')", $serialsMigration);
        $this->assertStringContainsString("constrained('batches', 'id', 'serials_batch_id_fk')", $serialsMigration);
    }

    public function test_finance_and_inventory_high_volume_paths_keep_composite_indexes(): void
    {
        $requiredIndexChecks = [
            'app/Modules/Finance/database/migrations/2024_01_01_120003a_create_journal_entries_table.php' => [
                'journal_entries_tenant_period_status_idx',
                'journal_entries_tenant_reference_uk',
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120004a_create_ar_transactions_table.php' => [
                'ar_transactions_tenant_reference_uk',
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120004b_create_ap_transactions_table.php' => [
                'ap_transactions_tenant_reference_uk',
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120005b_create_payments_table.php' => [
                'payments_tenant_party_idx',
                'payments_tenant_status_date_idx',
            ],
            'app/Modules/Inventory/database/migrations/2024_01_01_900003_create_stock_levels_table.php' => [
                'stock_levels_tenant_product_idx',
                'stock_levels_tenant_location_product_idx',
            ],
            'app/Modules/Inventory/database/migrations/2024_01_01_900004_create_stock_movements_table.php' => [
                'stock_movements_tenant_product_date_idx',
                'stock_movements_tenant_ref_idx',
                'stock_movements_tenant_from_loc_date_idx',
                'stock_movements_tenant_to_loc_date_idx',
            ],
            'app/Modules/Inventory/database/migrations/2024_01_01_900006_create_stock_reservations_table.php' => [
                'stock_reservations_tenant_expiry_idx',
                'stock_reservations_tenant_product_location_idx',
                'stock_reservations_tenant_reserved_for_idx',
            ],
        ];

        foreach ($requiredIndexChecks as $relativePath => $indexNames) {
            $contents = $this->readSource($relativePath);

            foreach ($indexNames as $indexName) {
                $this->assertStringContainsString(
                    $indexName,
                    $contents,
                    'Missing high-volume query composite index in migration: '.$relativePath.' -> '.$indexName
                );
            }
        }
    }

    public function test_org_unit_aware_business_uniques_include_tenant_and_org_unit_scope(): void
    {
        $requiredScopedUniques = [
            'app/Modules/Customer/database/migrations/2024_01_01_400001_create_customers_table.php' => [
                "unique(['tenant_id', 'org_unit_id', 'customer_code']",
            ],
            'app/Modules/Employee/database/migrations/2024_01_01_300004_create_employees_table.php' => [
                "unique(['tenant_id', 'org_unit_id', 'employee_code']",
            ],
            'app/Modules/Warehouse/database/migrations/2024_01_01_800001_create_warehouses_table.php' => [
                "unique(['tenant_id', 'org_unit_id', 'code']",
            ],
            'app/Modules/Purchase/database/migrations/2024_01_01_100001_create_purchase_orders_table.php' => [
                "unique(['tenant_id', 'org_unit_id', 'po_number']",
            ],
            'app/Modules/Sales/database/migrations/2024_01_01_110001_create_sales_orders_table.php' => [
                "unique(['tenant_id', 'org_unit_id', 'so_number']",
            ],
            'app/Modules/Product/database/migrations/2024_01_01_600005_create_products_table.php' => [
                "unique(['tenant_id', 'org_unit_id', 'sku']",
                "unique(['tenant_id', 'org_unit_id', 'slug']",
            ],
            'app/Modules/Pricing/database/migrations/2024_01_01_700001_create_price_lists_table.php' => [
                "unique(['tenant_id', 'org_unit_id', 'name']",
            ],
        ];

        foreach ($requiredScopedUniques as $relativePath => $fragments) {
            $contents = $this->readSource($relativePath);

            foreach ($fragments as $fragment) {
                $this->assertStringContainsString(
                    $fragment,
                    $contents,
                    'Missing org-unit scoped unique constraint in migration: '.$relativePath.' -> '.$fragment
                );
            }
        }
    }

    public function test_finance_replay_uniques_remain_tenant_scoped_for_idempotency_contracts(): void
    {
        $requiredTenantScopedReplayUniques = [
            'app/Modules/Finance/database/migrations/2024_01_01_120003a_create_journal_entries_table.php' => [
                "unique(['tenant_id', 'reference_type', 'reference_id'], 'journal_entries_tenant_reference_uk')",
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120004a_create_ar_transactions_table.php' => [
                "unique(['tenant_id', 'reference_type', 'reference_id'], 'ar_transactions_tenant_reference_uk')",
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120004b_create_ap_transactions_table.php' => [
                "unique(['tenant_id', 'reference_type', 'reference_id'], 'ap_transactions_tenant_reference_uk')",
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120005b_create_payments_table.php' => [
                "unique(['tenant_id', 'idempotency_key'], 'payments_tenant_idempotency_key_uk')",
            ],
        ];

        foreach ($requiredTenantScopedReplayUniques as $relativePath => $fragments) {
            $contents = $this->readSource($relativePath);

            foreach ($fragments as $fragment) {
                $this->assertStringContainsString(
                    $fragment,
                    $contents,
                    'Missing tenant-scoped replay/idempotency unique contract in migration: '.$relativePath.' -> '.$fragment
                );
            }
        }
    }

    public function test_org_unit_aware_migrations_do_not_keep_tenant_only_uniques_except_allowlisted_contracts(): void
    {
        $allowlistedTenantOnlyUniques = [
            'app/Modules/Finance/database/migrations/2024_01_01_120003a_create_journal_entries_table.php' => [
                'journal_entries_tenant_reference_uk',
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120004a_create_ar_transactions_table.php' => [
                'ar_transactions_tenant_reference_uk',
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120004b_create_ap_transactions_table.php' => [
                'ap_transactions_tenant_reference_uk',
            ],
            'app/Modules/Finance/database/migrations/2024_01_01_120005b_create_payments_table.php' => [
                'payments_tenant_idempotency_key_uk',
            ],
        ];

        $violations = [];

        foreach ($this->moduleMigrationFiles() as $filePath) {
            $contents = file_get_contents($filePath);
            if ($contents === false) {
                $this->fail('Unable to read migration file: '.$filePath);
            }

            if (! str_contains($contents, "'org_unit_id'")) {
                continue;
            }

            preg_match_all(
                '/unique\(\s*\[([^\]]+)\]\s*,\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/s',
                $contents,
                $matches,
                PREG_SET_ORDER
            );

            $relativePath = str_replace('\\', '/', str_replace($this->repoRoot.'/', '', $filePath));

            foreach ($matches as $match) {
                $rawColumns = $match[1] ?? '';
                $indexName = $match[2] ?? '';

                if ($rawColumns === '' || $indexName === '') {
                    continue;
                }

                $columns = array_map(
                    static fn (string $part): string => trim($part, " \t\n\r\0\x0B'\""),
                    explode(',', $rawColumns)
                );

                $columns = array_values(array_filter($columns, static fn (string $column): bool => $column !== ''));

                if ($columns === [] || $columns[0] !== 'tenant_id') {
                    continue;
                }

                if (in_array('org_unit_id', $columns, true)) {
                    continue;
                }

                $allowedForFile = $allowlistedTenantOnlyUniques[$relativePath] ?? [];
                if (in_array($indexName, $allowedForFile, true)) {
                    continue;
                }

                $violations[] = $relativePath.' -> '.$indexName.' ('.implode(', ', $columns).')';
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Org-unit-aware migrations must not keep tenant-only unique keys unless explicitly allowlisted.\n"
            .implode("\n", $violations)
        );
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
