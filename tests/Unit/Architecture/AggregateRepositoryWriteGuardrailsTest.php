<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class AggregateRepositoryWriteGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_sales_aggregate_repositories_keep_transactional_tenant_safe_line_sync_patterns(): void
    {
        $repositories = [
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesOrderRepository.php',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesInvoiceRepository.php',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentShipmentRepository.php',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesReturnRepository.php',
        ];

        foreach ($repositories as $relativePath) {
            $path = $this->repoRoot.'/'.$relativePath;
            $this->assertFileExists($path, "Expected repository not found: {$relativePath}");

            $source = (string) file_get_contents($path);

            $this->assertStringContainsString('return DB::transaction(function () use (', $source, "{$relativePath} must keep save() transactional.");
            $this->assertStringContainsString('if ($', $source, "{$relativePath} must distinguish update vs create paths.");
            $this->assertMatchesRegularExpression('/\$this->update\([^\n]*->getId\(\), \$data\)/', $source, "{$relativePath} must delegate ID-based writes through the shared scoped update helper.");
            $this->assertStringContainsString("->where('tenant_id', (int) \$model->tenant_id)", $source, "{$relativePath} line sync must guard child writes by tenant_id.");
            $this->assertStringContainsString('whereNotIn(\'id\', $keptLineIds)->delete();', $source, "{$relativePath} must prune removed child rows during aggregate sync.");
            $this->assertStringContainsString("\$model->load('lines');", $source, "{$relativePath} must reload child lines before mapping to domain.");
        }
    }

    public function test_hr_payslip_repository_keeps_transactional_tenant_safe_line_upsert_pattern(): void
    {
        $relativePath = 'app/Modules/HR/Infrastructure/Persistence/Eloquent/Repositories/EloquentPayslipRepository.php';
        $path = $this->repoRoot.'/'.$relativePath;

        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('DB::transaction(function () use ($e, $data): PayslipModel {', $source);
        $this->assertMatchesRegularExpression('/\$this->update\(\$e->getId\(\), \$data\)/', $source, 'Payslip save must delegate ID-based writes through the shared scoped update helper.');
        $this->assertStringContainsString("'tenant_id' => \$e->getTenantId()", $source);
        $this->assertStringContainsString('PayslipLineModel::query()->updateOrCreate(', $source);
        $this->assertStringContainsString("->where('tenant_id', \$e->getTenantId())", $source);
        $this->assertStringContainsString("->where('payslip_id', (int) \$model->id)", $source);
        $this->assertStringContainsString("->whereNotIn('item_code', \$lineCodes === [] ? ['__none__'] : \$lineCodes)", $source);
    }

    public function test_purchase_header_repositories_delegate_updates_through_shared_scoped_base_repository(): void
    {
        $repositories = [
            'app/Modules/Purchase/Infrastructure/Persistence/Eloquent/Repositories/EloquentPurchaseOrderRepository.php',
            'app/Modules/Purchase/Infrastructure/Persistence/Eloquent/Repositories/EloquentPurchaseInvoiceRepository.php',
            'app/Modules/Purchase/Infrastructure/Persistence/Eloquent/Repositories/EloquentPurchaseReturnRepository.php',
            'app/Modules/Purchase/Infrastructure/Persistence/Eloquent/Repositories/EloquentGrnHeaderRepository.php',
        ];

        foreach ($repositories as $relativePath) {
            $path = $this->repoRoot.'/'.$relativePath;
            $this->assertFileExists($path, "Expected repository not found: {$relativePath}");

            $source = (string) file_get_contents($path);

            $this->assertMatchesRegularExpression('/\$this->update\(\$entity->getId\(\), \$data\)/', $source, "{$relativePath} must route ID-based writes through the shared scoped update helper.");
            $this->assertStringContainsString('return $this->toDomainEntity($model);', $source, "{$relativePath} must continue mapping persisted state through the shared domain mapper.");
        }
    }
}
