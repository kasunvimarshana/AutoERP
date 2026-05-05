<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class PolymorphicGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_app_service_provider_enforces_morph_map(): void
    {
        $source = $this->readSource('app/Providers/AppServiceProvider.php');

        $this->assertStringContainsString('Relation::enforceMorphMap([', $source);

        $requiredAliases = [
            "'sales_invoice'",
            "'purchase_invoice'",
            "'sales_order'",
            "'purchase_order'",
            "'grn'",
            "'shipment'",
            "'payment'",
            "'purchase_payment'",
            "'sales_payment'",
            "'purchase_return'",
            "'sales_return'",
            "'stock_movement'",
            "'payroll_run'",
            "'cycle_count_headers'",
            "'cycle_count'",
            "'transfer_order'",
            "'sales_order_line'",
            "'sales_order_lines'",
            "'customer'",
            "'supplier'",
            "'employee'",
            "'warehouse_location'",
            "'product'",
            "'product_variant'",
            "'batch'",
            "'serial'",
            "'user'",
            "'tenant'",
            "'org_unit'",
            "'account'",
            "'cost_center'",
            "'journal_entry'",
            "'ar_transaction'",
            "'ap_transaction'",
            "'stock_level'",
            "'stock_reservation'",
        ];

        foreach ($requiredAliases as $alias) {
            $this->assertStringContainsString($alias, $source, 'Missing morph map alias: '.$alias);
        }
    }

    public function test_polymorphic_models_define_explicit_type_class_accessors(): void
    {
        $requiredMethods = [
            'app/Modules/Audit/Infrastructure/Persistence/Eloquent/Models/AuditLogModel.php' => [
                'getAuditableTypeClassAttribute',
            ],
            'app/Modules/Finance/Infrastructure/Persistence/Eloquent/Models/PaymentAllocationModel.php' => [
                'getInvoiceTypeClassAttribute',
            ],
            'app/Modules/Finance/Infrastructure/Persistence/Eloquent/Models/ArTransactionModel.php' => [
                'getReferenceTypeClassAttribute',
            ],
            'app/Modules/Finance/Infrastructure/Persistence/Eloquent/Models/ApTransactionModel.php' => [
                'getReferenceTypeClassAttribute',
            ],
            'app/Modules/Finance/Infrastructure/Persistence/Eloquent/Models/JournalEntryModel.php' => [
                'getReferenceTypeClassAttribute',
            ],
            'app/Modules/Inventory/Infrastructure/Persistence/Eloquent/Models/StockMovementModel.php' => [
                'getReferenceTypeClassAttribute',
            ],
            'app/Modules/Inventory/Infrastructure/Persistence/Eloquent/Models/InventoryCostLayerModel.php' => [
                'getReferenceTypeClassAttribute',
            ],
            'app/Modules/Inventory/Infrastructure/Persistence/Eloquent/Models/StockReservationModel.php' => [
                'getReservedForTypeClassAttribute',
            ],
            'app/Modules/Inventory/Infrastructure/Persistence/Eloquent/Models/SerialModel.php' => [
                'getCurrentOwnerTypeClassAttribute',
            ],
            'app/Modules/Inventory/Infrastructure/Persistence/Eloquent/Models/TraceLogModel.php' => [
                'getEntityTypeClassAttribute',
                'getReferenceTypeClassAttribute',
            ],
        ];

        foreach ($requiredMethods as $relativePath => $methods) {
            $source = $this->readSource($relativePath);

            $this->assertStringContainsString('ResolvesMorphTypeClass', $source, 'Model must use morph type resolver trait: '.$relativePath);
            foreach ($methods as $method) {
                $this->assertStringContainsString($method, $source, 'Missing type class accessor in '.$relativePath.' -> '.$method);
            }
        }
    }

    private function readSource(string $relativePath): string
    {
        $absolutePath = $this->repoRoot.'/'.str_replace('\\', '/', $relativePath);
        $source = file_get_contents($absolutePath);

        if ($source === false) {
            $this->fail('Unable to read source file: '.$relativePath);
        }

        return $source;
    }
}
