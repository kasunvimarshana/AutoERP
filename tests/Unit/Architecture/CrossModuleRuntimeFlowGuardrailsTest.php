<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class CrossModuleRuntimeFlowGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_runtime_sequence_matrix_file_exists_and_contains_required_flow_sections(): void
    {
        $relativePath = 'docs/architecture/runtime-sequence-matrix.md';
        $contents = $this->readSource($relativePath);

        $this->assertStringContainsString('## 1. Procure-to-Pay (P2P)', $contents);
        $this->assertStringContainsString('## 2. Order-to-Cash (O2C)', $contents);
        $this->assertStringContainsString('## 3. Inventory Movements and Valuation', $contents);
        $this->assertStringContainsString('## 4. HR to Finance Posting', $contents);
    }

    public function test_p2p_and_o2c_route_actions_exist_in_route_files(): void
    {
        $purchaseRoutes = $this->readSource('app/Modules/Purchase/routes/api.php');
        $salesRoutes = $this->readSource('app/Modules/Sales/routes/api.php');

        $this->assertStringContainsString('purchase-orders/{purchaseOrder}/confirm', $purchaseRoutes);
        $this->assertStringContainsString('grns/{grn}/post', $purchaseRoutes);
        $this->assertStringContainsString('purchase-invoices/{invoice}/approve', $purchaseRoutes);
        $this->assertStringContainsString('purchase-invoices/{invoice}/payment', $purchaseRoutes);

        $this->assertStringContainsString('sales-orders/{salesOrder}/confirm', $salesRoutes);
        $this->assertStringContainsString('shipments/{shipment}/process', $salesRoutes);
        $this->assertStringContainsString('sales-invoices/{salesInvoice}/post', $salesRoutes);
        $this->assertStringContainsString('sales-invoices/{salesInvoice}/record-payment', $salesRoutes);
    }

    public function test_cross_module_finance_and_inventory_listeners_exist_for_transactional_flows(): void
    {
        $requiredFiles = [
            'app/Modules/Finance/Infrastructure/Listeners/HandlePurchaseInvoiceApproved.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandlePurchasePaymentRecorded.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandleSalesInvoicePosted.php',
            'app/Modules/Inventory/Infrastructure/Listeners/HandleGoodsReceiptPosted.php',
            'app/Modules/Inventory/Infrastructure/Listeners/HandleShipmentProcessed.php',
        ];

        foreach ($requiredFiles as $relativePath) {
            $this->assertFileExists(
                $this->repoRoot.'/'.$relativePath,
                'Missing required listener file: '.$relativePath
            );
        }
    }

    public function test_flow_level_domain_event_files_exist(): void
    {
        $requiredFiles = [
            'app/Modules/Purchase/Domain/Events/PurchaseOrderConfirmed.php',
            'app/Modules/Purchase/Domain/Events/GoodsReceiptPosted.php',
            'app/Modules/Purchase/Domain/Events/PurchaseInvoiceApproved.php',
            'app/Modules/Purchase/Domain/Events/PurchasePaymentRecorded.php',
            'app/Modules/Sales/Domain/Events/ShipmentProcessed.php',
            'app/Modules/Sales/Domain/Events/SalesInvoicePosted.php',
            'app/Modules/Sales/Domain/Events/SalesPaymentRecorded.php',
            'app/Modules/HR/Domain/Events/PayrollRunApproved.php',
            'app/Modules/HR/Domain/Events/PayslipGenerated.php',
        ];

        foreach ($requiredFiles as $relativePath) {
            $this->assertFileExists(
                $this->repoRoot.'/'.$relativePath,
                'Missing required domain event file: '.$relativePath
            );
        }
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
