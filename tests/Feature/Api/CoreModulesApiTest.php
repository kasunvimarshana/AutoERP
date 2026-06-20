<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Models\Invoice;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemCreationService;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\DTOs\PurchaseOrderLineData;
use Modules\Purchase\Services\GoodsReceiptNoteService;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Payment\Services\PaymentAuthorizationService;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\UserModel;
use Tests\TestCase;

final class CoreModulesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->mock(SupplierAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
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
        $supplierId = $this->supplier($tenantId, $organizationUnitId);
        $uomId = $this->uom($tenantId, $organizationUnitId);
        $item = $this->createItemDirect($tenantId, $organizationUnitId, $uomId, 'PUR-ITEM');

        $orders = app(PurchaseOrderService::class);
        $order = $orders->create(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            organizationUnitId: $organizationUnitId,
            supplierType: 'supplier',
            supplierId: $supplierId,
            warehouseId: $warehouseId,
            lines: [new PurchaseOrderLineData((int) $item['id'], '5.000000', '100.000000', uomId: $uomId)],
        ));
        $order = $orders->approve($orders->submit($order))->load('lines');
        $grn = app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            organizationUnitId: $organizationUnitId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [new GoodsReceiptNoteLineData((int) $item['id'], '2.000000', '2.000000', '100.000000', purchaseOrderLineId: (int) $order->lines[0]->getKey(), uomId: $uomId, orderedQuantity: '5.000000')],
        ));
        app(GoodsReceiptNoteService::class)->post($grn);

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
            ->assertJsonPath('data.balance.remaining_amount', '120.000000')
            ->assertJsonPath('data.lines.0.line_total', '120.000000')
            ->json('data');

        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        $this->postJson('/api/v1/invoices/'.$invoice['id'].'/approve', $scope)->assertSuccessful();
        $this->postJson('/api/v1/invoices/'.$invoice['id'].'/post', $scope)
            ->assertSuccessful()->assertJsonPath('data.status', 'posted');
        $this->getJson('/api/v1/invoices/'.$invoice['id'].'/balance?'.http_build_query($scope))
            ->assertSuccessful()->assertJsonPath('data.remainingAmount', '120.000000');
    }

    public function test_invoice_request_validates_consumed_nested_fields(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $payload = $this->invoicePayload($tenantId, $organizationUnitId);
        $payload['lines'][0]['metadata'] = 'not-an-array';

        $this->postJson('/api/v1/invoices/preview', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('lines.0.metadata');
    }

    public function test_payment_creation_and_invoice_allocation_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->actingAsSuperAdministrator($tenantId, $organizationUnitId, [
            PaymentAuthorizationService::PAYMENTS_CREATE,
            PaymentAuthorizationService::PAYMENTS_SUBMIT,
            PaymentAuthorizationService::PAYMENTS_APPROVE,
            PaymentAuthorizationService::PAYMENTS_POST,
            PaymentAuthorizationService::PAYMENTS_ALLOCATE,
        ], 'Payment');
        $this->paymentFinanceContext($tenantId, $organizationUnitId);
        $invoice = $this->createPostedInvoice($tenantId, $organizationUnitId);
        $paymentMethodId = $this->paymentMethod($tenantId);

        $payment = $this->postJson('/api/v1/payments', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_type' => 'customer_receipt',
            'direction' => 'inbound',
            'payment_date' => '2026-06-06',
            'party_type' => 'customer',
            'party_id' => $invoice->party_id,
            'lines' => [[
                'payment_method_id' => $paymentMethodId,
                'amount' => '80.000000',
            ]],
        ])->assertSuccessful()->json('data');

        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        $this->postJson('/api/v1/payments/'.$payment['id'].'/submit-approval', $scope)->assertSuccessful();
        $this->postJson('/api/v1/payments/'.$payment['id'].'/approve', $scope)->assertSuccessful();
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
        $this->fiscalPeriod($tenantId, $organizationUnitId);

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
        $this->actingAsSuperAdministrator($tenantId, $organizationUnitId, [
            ItemAuthorizationService::VIEW,
            ItemAuthorizationService::CREATE,
            ItemAuthorizationService::UPDATE,
        ], 'Item');

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

    /**
     * @param  list<string>  $permissions
     */
    private function actingAsSuperAdministrator(
        int $tenantId,
        int $organizationUnitId,
        array $permissions,
        string $module,
    ): void
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        $now = now();
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'first_name' => 'Item',
            'last_name' => 'Administrator',
            'email' => 'item-admin-'.Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('secret-password'),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => UserPermission::SUPER_ADMIN_ROLE,
            'guard_name' => $guard,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'tenant_id' => $tenantId,
                'name' => $permission,
                'guard_name' => $guard,
                'module' => $module,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs(UserModel::query()->findOrFail($userId));
    }

    private function createItemDirect(int $tenantId, int $organizationUnitId, int $uomId, string $code): array
    {
        $item = app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: $code,
            name: $code,
            itemType: ItemType::Stock,
            trackingType: TrackingType::None,
            costingMethod: CostingMethod::Fifo,
            baseUomId: $uomId,
            organizationUnitId: $organizationUnitId,
            isStockable: true,
        ));

        return ['id' => (int) $item->getKey()];
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

    private function supplier(int $tenantId, int $organizationUnitId): int
    {
        $code = 'SUP-'.Str::upper(Str::random(6));

        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_number' => $code,
            'code' => $code,
            'name' => 'Supplier '.$code,
            'display_name' => 'Supplier '.$code,
            'supplier_type' => 'local',
            'status' => 'active',
            'is_credit_allowed' => true,
            'is_advance_allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uom(int $tenantId, int $organizationUnitId): int
    {
        $code = 'PCS-'.Str::upper(Str::random(6));

        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => 1,
            'code' => $code,
            'name' => 'Pieces',
            'symbol' => 'pcs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoicePayload(int $tenantId, int $organizationUnitId): array
    {
        $customerId = $this->customer($tenantId, $organizationUnitId);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'invoice_type' => 'sales',
            'direction' => 'outbound',
            'invoice_date' => '2026-06-06',
            'party_type' => 'customer',
            'party_id' => $customerId,
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

    private function customer(int $tenantId, int $organizationUnitId): int
    {
        $code = 'CUS-'.Str::upper(Str::random(6));

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => $code,
            'code' => $code,
            'name' => 'Customer '.$code,
            'customer_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentMethod(int $tenantId): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'CASH-'.Str::upper(Str::random(6)),
            'name' => 'Cash',
            'method_type' => 'cash',
            'direction_allowed' => 'inbound',
            'requires_reference' => false,
            'requires_bank_account' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentFinanceContext(int $tenantId, int $organizationUnitId): void
    {
        $this->fiscalPeriod($tenantId, $organizationUnitId);
        $assetTypeId = $this->accountType($tenantId, 'PAYMENT-ASSET', 'debit');
        $cashAccountId = (int) DB::table('finance_accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_type_id' => $assetTypeId,
            'code' => '1010',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_posting_account' => true,
            'is_cash_account' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $receivableAccountId = (int) DB::table('finance_accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_type_id' => $assetTypeId,
            'code' => '1100',
            'name' => 'Accounts Receivable',
            'normal_balance' => 'debit',
            'is_posting_account' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $profileId = (int) DB::table('finance_posting_profiles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'payment_received',
            'name' => 'Payment Received',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('finance_posting_profile_rules')->insert([
            [
                'posting_profile_id' => $profileId,
                'line_key' => 'cash',
                'account_id' => $cashAccountId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'posting_profile_id' => $profileId,
                'line_key' => 'receivable',
                'account_id' => $receivableAccountId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
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

    private function fiscalPeriod(int $tenantId, int $organizationUnitId): void
    {
        $yearId = (int) DB::table('finance_fiscal_years')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('finance_fiscal_periods')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'fiscal_year_id' => $yearId,
            'name' => 'June 2026',
            'period_number' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
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
