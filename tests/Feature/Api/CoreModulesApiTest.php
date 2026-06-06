<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Models\Invoice;
use Tests\TestCase;

final class CoreModulesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_supplier_crud_lookup_validation_and_tenant_isolation(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        [$otherTenantId, $otherOrganizationUnitId] = $this->scope();

        $created = $this->postJson('/api/v1/suppliers', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'SUP-API',
            'name' => 'API Supplier',
            'supplier_type' => 'company',
            'status' => 'active',
            'contacts' => [['contact_name' => 'Primary Contact', 'is_primary' => true]],
        ])->assertSuccessful()->json('data');

        $this->patchJson('/api/v1/suppliers/'.$created['id'], [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'name' => 'Updated API Supplier',
        ])->assertSuccessful()->assertJsonPath('data.name', 'Updated API Supplier');

        $this->postJson('/api/v1/suppliers', [
            'tenant_id' => $otherTenantId,
            'organization_unit_id' => $otherOrganizationUnitId,
            'code' => 'SUP-OTHER',
            'name' => 'Other Supplier',
            'supplier_type' => 'company',
        ])->assertSuccessful();

        $this->getJson('/api/v1/suppliers?tenant_id='.$tenantId.'&organization_unit_id='.$organizationUnitId)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'SUP-API');

        $this->postJson('/api/v1/suppliers', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'SUP-BAD',
            'name' => 'Invalid Supplier',
            'supplier_type' => 'company',
            'credit_limit' => '-1',
        ])->assertStatus(422)->assertJsonPath('error.type', 'validation');
    }

    public function test_item_crud_and_lookup_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();

        $item = $this->createItemViaApi($tenantId, $organizationUnitId, 'ITEM-API');

        $this->patchJson('/api/v1/items/'.$item['id'], [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'name' => 'Updated API Item',
        ])->assertSuccessful()->assertJsonPath('data.name', 'Updated API Item');

        $this->getJson('/api/v1/items/lookup/stockable?tenant_id='.$tenantId.'&organization_unit_id='.$organizationUnitId)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'ITEM-API');
    }

    public function test_purchase_order_grn_and_inventory_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $warehouseId = $this->warehouse($tenantId, $organizationUnitId);
        $item = $this->createItemViaApi($tenantId, $organizationUnitId, 'PUR-ITEM');

        $order = $this->postJson('/api/v1/purchase/orders', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'purchase_order_date' => '2026-06-06',
            'warehouse_id' => $warehouseId,
            'lines' => [[
                'item_id' => $item['id'],
                'ordered_quantity' => '5.000000',
                'unit_price' => '100.000000',
            ]],
        ])->assertSuccessful()->json('data');

        $grn = $this->postJson('/api/v1/purchase/goods-receipts', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'received_date' => '2026-06-06',
            'purchase_order_id' => $order['id'],
            'warehouse_id' => $warehouseId,
            'lines' => [[
                'purchase_order_line_id' => $order['lines'][0]['id'],
                'item_id' => $item['id'],
                'received_quantity' => '2.000000',
                'accepted_quantity' => '2.000000',
                'ordered_quantity' => '5.000000',
                'unit_price' => '100.000000',
            ]],
        ])->assertSuccessful()->json('data');

        $this->postJson('/api/v1/purchase/goods-receipts/'.$grn['id'].'/post', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ])->assertSuccessful()->assertJsonPath('data.status', 'posted');

        $this->getJson('/api/v1/inventory/availability?'.http_build_query([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'item_id' => $item['id'],
            'warehouse_id' => $warehouseId,
        ]))->assertSuccessful()->assertJsonPath('data.quantityOnHand', '2.000000');

        $this->postJson('/api/v1/inventory/reservations', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'reservation_date' => '2026-06-06',
            'item_id' => $item['id'],
            'warehouse_id' => $warehouseId,
            'quantity_reserved' => '1.000000',
        ])->assertSuccessful()->assertJsonPath('data.quantity_reserved', '1.000000');
    }

    public function test_invoice_preview_create_and_lifecycle_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $payload = $this->invoicePayload($tenantId, $organizationUnitId);

        $this->postJson('/api/v1/invoices/preview', $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.grandTotal', '120.000000');

        $invoice = $this->postJson('/api/v1/invoices', $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.grand_total', '120.000000')
            ->json('data');

        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        $this->postJson('/api/v1/invoices/'.$invoice['id'].'/approve', $scope)->assertSuccessful();
        $this->postJson('/api/v1/invoices/'.$invoice['id'].'/post', $scope)
            ->assertSuccessful()->assertJsonPath('data.status', 'posted');
        $this->getJson('/api/v1/invoices/'.$invoice['id'].'/balance?'.http_build_query($scope))
            ->assertSuccessful()->assertJsonPath('data.remainingAmount', '120.000000');
    }

    public function test_payment_creation_and_invoice_allocation_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $invoice = $this->createPostedInvoice($tenantId, $organizationUnitId);

        $payment = $this->postJson('/api/v1/payments', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_type' => 'customer_receipt',
            'direction' => 'inbound',
            'payment_date' => '2026-06-06',
            'lines' => [['amount' => '80.000000']],
        ])->assertSuccessful()->json('data');

        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        $this->postJson('/api/v1/payments/'.$payment['id'].'/post', $scope)->assertSuccessful();
        $this->postJson('/api/v1/payments/'.$payment['id'].'/allocations', $scope + [
            'allocations' => [[
                'invoice_id' => $invoice->getKey(),
                'allocated_amount' => '80.000000',
                'allocation_date' => '2026-06-06',
            ]],
        ])->assertSuccessful()
            ->assertJsonPath('data.allocated_amount', '80.000000')
            ->assertJsonPath('data.unapplied_amount', '0.000000');

        $this->assertSame('40.000000', (string) $invoice->refresh()->balance_due);
    }

    public function test_finance_account_and_journal_posting_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $debitType = $this->accountType($tenantId, 'ASSET', 'debit');
        $creditType = $this->accountType($tenantId, 'EQUITY', 'credit');

        $cash = $this->createAccountViaApi($tenantId, $organizationUnitId, $debitType, '1000', 'Cash', 'debit');
        $capital = $this->createAccountViaApi($tenantId, $organizationUnitId, $creditType, '3000', 'Capital', 'credit');

        $journal = $this->postJson('/api/v1/finance/journals', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'journal_date' => '2026-06-06',
            'lines' => [
                ['account_id' => $cash['id'], 'line_number' => 1, 'debit' => '1000.000000', 'credit' => '0.000000'],
                ['account_id' => $capital['id'], 'line_number' => 2, 'debit' => '0.000000', 'credit' => '1000.000000'],
            ],
        ])->assertSuccessful()->json('data');

        $this->postJson('/api/v1/finance/journals/'.$journal['id'].'/post', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ])->assertSuccessful()
            ->assertJsonPath('data.totalDebit', '1000.000000')
            ->assertJsonPath('data.totalCredit', '1000.000000')
            ->assertJsonPath('data.ledgerEntryCount', 2);
    }

    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$suffix,
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId];
    }

    private function createItemViaApi(int $tenantId, int $organizationUnitId, string $code): array
    {
        return $this->postJson('/api/v1/items', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $code,
            'item_type' => 'stock',
            'tracking_type' => 'none',
            'costing_method' => 'fifo',
            'is_stockable' => true,
        ])->assertSuccessful()->json('data');
    }

    private function warehouse(int $tenantId, int $organizationUnitId): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'name' => 'Warehouse '.Str::random(6),
            'code' => 'WH-'.Str::upper(Str::random(6)),
            'type' => 'standard',
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoicePayload(int $tenantId, int $organizationUnitId): array
    {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'invoice_type' => 'sales',
            'direction' => 'outbound',
            'invoice_date' => '2026-06-06',
            'lines' => [[
                'line_number' => 1,
                'description' => 'API invoice line',
                'quantity' => '2.000000',
                'unit_price' => '50.000000',
                'tax_amount' => '20.000000',
            ]],
        ];
    }

    private function createPostedInvoice(int $tenantId, int $organizationUnitId): Invoice
    {
        $invoice = $this->postJson('/api/v1/invoices', $this->invoicePayload($tenantId, $organizationUnitId))
            ->assertSuccessful()->json('data');
        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        $this->postJson('/api/v1/invoices/'.$invoice['id'].'/approve', $scope)->assertSuccessful();
        $this->postJson('/api/v1/invoices/'.$invoice['id'].'/post', $scope)->assertSuccessful();

        return Invoice::query()->findOrFail($invoice['id']);
    }

    private function accountType(int $tenantId, string $code, string $normalBalance): int
    {
        return (int) DB::table('finance_account_types')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $code,
            'normal_balance' => $normalBalance,
            'statement_type' => 'balance_sheet',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAccountViaApi(
        int $tenantId,
        int $organizationUnitId,
        int $accountTypeId,
        string $code,
        string $name,
        string $normalBalance,
    ): array {
        return $this->postJson('/api/v1/finance/accounts', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_type_id' => $accountTypeId,
            'code' => $code,
            'name' => $name,
            'normal_balance' => $normalBalance,
        ])->assertSuccessful()->json('data');
    }
}
