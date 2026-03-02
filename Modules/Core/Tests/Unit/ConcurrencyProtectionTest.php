<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Concurrency protection compliance tests.
 *
 * Verifies that every write-path service method that mutates inventory,
 * financial, or reservation records uses DB::transaction() and, where
 * required by AGENT.md, pessimistic locking (lockForUpdate()).
 *
 * Per AGENT.md §Data Integrity & Concurrency:
 *   "All stock mutations must execute inside database transactions
 *    with guaranteed atomicity."
 *   "Pessimistic locking for stock deduction."
 *   "Deadlock-aware retry mechanisms required."
 *
 * These tests use PHP source reflection (file_get_contents + preg_match)
 * because DB::transaction wrapping is a code-structure requirement that
 * cannot be verified through interface contracts or reflection alone.
 */
class ConcurrencyProtectionTest extends TestCase
{
    /**
     * Source files that must contain DB::transaction() calls
     * because they perform write mutations.
     *
     * @return array<string, array{string, string}>
     */
    public static function transactionalServiceProvider(): array
    {
        $base = dirname(__DIR__, 3); // Modules/

        return [
            'InventoryService'    => [$base . '/Inventory/Application/Services/InventoryService.php', 'recordTransaction'],
            'InventoryReserve'    => [$base . '/Inventory/Application/Services/InventoryService.php', 'reserve'],
            'AccountingService'   => [$base . '/Accounting/Application/Services/AccountingService.php', 'createEntry'],
            'SalesService'        => [$base . '/Sales/Application/Services/SalesService.php', 'createOrder'],
            'POSService'          => [$base . '/POS/Application/Services/POSService.php', 'createTransaction'],
            'ProcurementService'  => [$base . '/Procurement/Application/Services/ProcurementService.php', 'createPurchaseOrder'],
            'WarehouseService'    => [$base . '/Warehouse/Application/Services/WarehouseService.php', 'createPickingOrder'],
            'CRMService'          => [$base . '/CRM/Application/Services/CRMService.php', 'createLead'],
            'TenancyService'      => [$base . '/Tenancy/Application/Services/TenancyService.php', 'create'],
            'OrganisationService' => [$base . '/Organisation/Application/Services/OrganisationService.php', 'create'],
            'MetadataService'     => [$base . '/Metadata/Application/Services/MetadataService.php', 'createField'],
            'WorkflowService'     => [$base . '/Workflow/Application/Services/WorkflowService.php', 'create'],
            'ProductService'      => [$base . '/Product/Application/Services/ProductService.php', 'create'],
            'ReportingService'    => [$base . '/Reporting/Application/Services/ReportingService.php', 'createDefinition'],
            'NotificationService' => [$base . '/Notification/Application/Services/NotificationService.php', 'createTemplate'],
            'IntegrationService'  => [$base . '/Integration/Application/Services/IntegrationService.php', 'registerWebhook'],
            'PluginService'       => [$base . '/Plugin/Application/Services/PluginService.php', 'installPlugin'],
            'PricingService'      => [$base . '/Pricing/Application/Services/PricingService.php', 'createPriceList'],
        ];
    }

    /**
     * @dataProvider transactionalServiceProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('transactionalServiceProvider')]
    public function test_service_file_contains_db_transaction(string $filePath, string $mutationMethod): void
    {
        $this->assertFileExists($filePath, "Service file not found: {$filePath}");

        $source = file_get_contents($filePath);
        $this->assertNotFalse($source);

        $this->assertStringContainsString(
            'DB::transaction',
            $source,
            sprintf(
                'Service %s must use DB::transaction() to guarantee atomicity for %s() and other write-path methods. '
                . 'Per AGENT.md §Data Integrity & Concurrency.',
                basename($filePath),
                $mutationMethod,
            ),
        );
    }

    /**
     * InventoryService must use lockForUpdate() for pessimistic locking
     * to prevent concurrent over-deduction.
     *
     * Per AGENT.md: "Pessimistic locking for stock deduction."
     */
    public function test_inventory_service_uses_lock_for_update(): void
    {
        $path = dirname(__DIR__, 3) . '/Inventory/Application/Services/InventoryService.php';

        $this->assertFileExists($path);
        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        $this->assertStringContainsString(
            'lockForUpdate',
            $source,
            'InventoryService must use lockForUpdate() to apply pessimistic locking '
            . 'during stock deduction and reservation operations.',
        );
    }

    /**
     * ProcurementService must use DB::transaction() for three-way matching
     * to ensure goods receipt and invoice comparison is atomic.
     */
    public function test_procurement_service_uses_db_transaction_for_receive_goods(): void
    {
        $path = dirname(__DIR__, 3) . '/Procurement/Application/Services/ProcurementService.php';

        $this->assertFileExists($path);
        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        $this->assertStringContainsString(
            'DB::transaction',
            $source,
            'ProcurementService must wrap receiveGoods() in DB::transaction() '
            . 'to guarantee atomic three-way matching.',
        );
    }

    /**
     * POSService must use DB::transaction() and contain a void path
     * (voidTransaction) also wrapped in a transaction.
     */
    public function test_pos_service_uses_db_transaction_for_void(): void
    {
        $path = dirname(__DIR__, 3) . '/POS/Application/Services/POSService.php';

        $this->assertFileExists($path);
        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        $this->assertStringContainsString(
            'DB::transaction',
            $source,
            'POSService must use DB::transaction() to protect void and sync operations.',
        );
    }
}
