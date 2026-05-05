<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ArTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentAllocationModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollRunModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;
use Tests\TestCase;

class PolymorphicAliasResolutionIntegrationTest extends TestCase
{
    public function test_backward_compatible_aliases_resolve_to_expected_models(): void
    {
        $this->assertSame(PaymentModel::class, Relation::getMorphedModel('purchase_payment'));
        $this->assertSame(PaymentModel::class, Relation::getMorphedModel('sales_payment'));
        $this->assertSame(PurchaseReturnModel::class, Relation::getMorphedModel('purchase_return'));
        $this->assertSame(SalesReturnModel::class, Relation::getMorphedModel('sales_return'));
        $this->assertSame(PayrollRunModel::class, Relation::getMorphedModel('payroll_run'));
        $this->assertSame(SalesOrderLineModel::class, Relation::getMorphedModel('sales_order_lines'));
    }

    public function test_type_class_accessors_resolve_aliases_for_finance_and_inventory_models(): void
    {
        $ar = new ArTransactionModel;
        $ar->reference_type = 'purchase_payment';

        $allocation = new PaymentAllocationModel;
        $allocation->invoice_type = 'sales_invoice';

        $reservation = new StockReservationModel;
        $reservation->reserved_for_type = 'sales_order_lines';

        $this->assertSame(PaymentModel::class, $ar->reference_type_class);
        $this->assertSame('Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\Models\\SalesInvoiceModel', $allocation->invoice_type_class);
        $this->assertSame(SalesOrderLineModel::class, $reservation->reserved_for_type_class);
    }
}
