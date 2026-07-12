<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Finance\Constants\FinancePermission;
use Modules\Invoice\Constants\InvoicePermission;
use Modules\Invoice\Models\Invoice;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemCreationService;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\DTOs\PurchaseOrderLineData;
use Modules\Purchase\Services\GoodsReceiptNoteService;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Models\UserModel;
use Tests\Support\FinancePostingFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class CoreModulesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $this->mock(SupplierAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_supplier_crud_lookup_validation_and_tenant_isolation(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        [$otherTenantId, $otherOrganizationUnitId] = $this->scope();
        $this->actingAsSuperAdministrator($tenantId, $organizationUnitId, [
            SupplierAuthorizationService::VIEW,
            SupplierAuthorizationService::CREATE,
            SupplierAuthorizationService::UPDATE,
        ], 'Supplier');

        $created = $this->tenantPostJson($tenantId, '/api/v1/suppliers', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'SUP-API',
            'name' => 'API Supplier',
            'supplier_type' => 'company',
            'status' => 'active',
            'contacts' => [['contact_name' => 'Primary Contact', 'is_primary' => true]],
        ])->assertSuccessful()->json('data');

        $this->tenantPatchJson($tenantId, '/api/v1/suppliers/'.$created['id'], [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => $created['row_version'],
            'name' => 'Updated API Supplier',
        ])->assertSuccessful()->assertJsonPath('data.name', 'Updated API Supplier');

        $this->tenantPostJson($otherTenantId, '/api/v1/suppliers', [
            'tenant_id' => $otherTenantId,
            'organization_unit_id' => $otherOrganizationUnitId,
            'code' => 'SUP-OTHER',
            'name' => 'Other Supplier',
            'supplier_type' => 'company',
        ])->assertSuccessful();

        $this->tenantGetJson($tenantId, '/api/v1/suppliers?tenant_id='.$tenantId.'&organization_unit_id='.$organizationUnitId)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'SUP-API');

        $this->tenantPostJson($tenantId, '/api/v1/suppliers', [
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

        $this->tenantPatchJson($tenantId, '/api/v1/items/'.$item['id'], [
            'tenant_id' => $tenantId,
            'name' => 'Updated API Item',
        ])->assertSuccessful()->assertJsonPath('data.name', 'Updated API Item');

        $this->tenantGetJson($tenantId, '/api/v1/items/lookup/stockable?tenant_id='.$tenantId.'&organization_unit_id='.$organizationUnitId)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'ITEM-API');
    }

    public function test_purchase_order_grn_and_inventory_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->actingAsSuperAdministrator($tenantId, $organizationUnitId, [], 'Inventory');
        $warehouseId = $this->warehouse($tenantId, $organizationUnitId);
        $supplierId = $this->supplier($tenantId, $organizationUnitId);
        $uomId = $this->uom($tenantId, $organizationUnitId);
        $item = $this->createItemDirect($tenantId, $organizationUnitId, $uomId, 'PUR-ITEM');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationUnitId, $warehouseId, $supplierId, $uomId, $item): void {
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
        });

        $this->tenantGetJson($tenantId, '/api/v1/inventory/availability?'.http_build_query([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'item_id' => $item['id'],
            'warehouse_id' => $warehouseId,
        ]))->assertSuccessful()->assertJsonPath('data.quantityOnHand', '2.000000');

        $this->tenantPostJson($tenantId, '/api/v1/inventory/reservations', [
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
        $this->actingAsSuperAdministrator($tenantId, $organizationUnitId, [
            InvoicePermission::PREVIEW,
            InvoicePermission::CREATE,
            InvoicePermission::APPROVE,
            InvoicePermission::POST,
            InvoicePermission::VIEW_BALANCE,
        ], 'Invoice');
        $payload = $this->invoicePayload($tenantId, $organizationUnitId);

        $this->tenantPostJson($tenantId, '/api/v1/invoices/preview', $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.grandTotal', '100.000000');

        $invoicePayload = $payload + ['idempotency_key' => 'invoice-'.Str::uuid()->toString()];
        $invoice = $this->tenantPostJson($tenantId, '/api/v1/invoices', $invoicePayload)
            ->assertSuccessful()
            ->assertJsonPath('data.grand_total', '100.000000')
            ->assertJsonPath('data.balance.remaining_amount', '100.000000')
            ->assertJsonPath('data.lines.0.line_total', '100.000000')
            ->json('data');

        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        $invoice = $this->tenantPostJson($tenantId, '/api/v1/invoices/'.$invoice['id'].'/approve', $scope + [
            'expected_version' => $invoice['row_version'],
        ])->assertSuccessful()->json('data');
        $this->tenantPostJson($tenantId, '/api/v1/invoices/'.$invoice['id'].'/post', $scope + [
            'expected_version' => $invoice['row_version'],
        ])
            ->assertSuccessful()->assertJsonPath('data.status', 'posted');
        $this->tenantGetJson($tenantId, '/api/v1/invoices/'.$invoice['id'].'/balance?'.http_build_query($scope))
            ->assertSuccessful()->assertJsonPath('data.remainingAmount', '100.000000');
    }

    public function test_invoice_request_validates_consumed_nested_fields(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->actingAsSuperAdministrator($tenantId, $organizationUnitId, [
            InvoicePermission::PREVIEW,
        ], 'Invoice');
        $payload = $this->invoicePayload($tenantId, $organizationUnitId);
        $payload['lines'][0]['metadata'] = 'not-an-array';

        $this->tenantPostJson($tenantId, '/api/v1/invoices/preview', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('lines.0.metadata');
    }

    public function test_payment_creation_and_invoice_allocation_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->actingAsSuperAdministrator($tenantId, $organizationUnitId, [
            PaymentPermission::PAYMENTS_CREATE,
            PaymentPermission::PAYMENTS_SUBMIT,
            PaymentPermission::PAYMENTS_APPROVE,
            PaymentPermission::PAYMENTS_POST,
            PaymentPermission::PAYMENTS_ALLOCATE,
        ], 'Payment');
        $this->paymentFinanceContext($tenantId, $organizationUnitId);
        $invoice = $this->createPostedInvoice($tenantId, $organizationUnitId);
        $paymentMethodId = $this->paymentMethod($tenantId);

        $payment = $this->tenantPostJson($tenantId, '/api/v1/payments', [
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
        $payment = $this->tenantPostJson($tenantId, '/api/v1/payments/'.$payment['id'].'/submit-approval', $scope + [
            'expected_version' => $payment['row_version'],
        ])->assertSuccessful()->json('data');
        $payment = $this->tenantPostJson($tenantId, '/api/v1/payments/'.$payment['id'].'/approve', $scope + [
            'expected_version' => $payment['row_version'],
        ])->assertSuccessful()->json('data');
        $payment = $this->tenantPostJson($tenantId, '/api/v1/payments/'.$payment['id'].'/post', $scope + [
            'expected_version' => $payment['row_version'],
        ])->assertSuccessful()->json('data');
        $this->tenantPostJson($tenantId, '/api/v1/payments/'.$payment['id'].'/allocations', $scope + [
            'expected_version' => $payment['row_version'],
            'allocations' => [[
                'invoice_id' => $invoice->getKey(),
                'allocated_amount' => '80.000000',
                'allocation_date' => '2026-06-06',
            ]],
        ])->assertSuccessful()
            ->assertJsonPath('data.allocated_amount', '80.000000')
            ->assertJsonPath('data.unapplied_amount', '0.000000');

        $balanceDue = $this->withTenantExecutionContext(
            $tenantId,
            fn (): string => (string) $invoice->refresh()->balance_due,
        );
        $this->assertSame('20.000000', $balanceDue);
    }

    public function test_finance_account_and_journal_posting_api(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->actingAsSuperAdministrator($tenantId, $organizationUnitId, [
            FinancePermission::ACCOUNTS_MANAGE,
            FinancePermission::JOURNALS_CREATE,
            FinancePermission::JOURNALS_POST,
        ], 'Finance');
        $debitType = $this->accountType($tenantId, 'ASSET', 'debit');
        $creditType = $this->accountType($tenantId, 'EQUITY', 'credit');

        $cash = $this->createAccountViaApi($tenantId, $organizationUnitId, $debitType, '1000', 'Cash', 'debit');
        $capital = $this->createAccountViaApi($tenantId, $organizationUnitId, $creditType, '3000', 'Capital', 'credit');

        $journal = $this->tenantPostJson($tenantId, '/api/v1/finance/journals', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'journal_date' => '2026-06-06',
            'lines' => [
                ['account_id' => $cash['id'], 'line_number' => 1, 'debit' => '1000.000000', 'credit' => '0.000000'],
                ['account_id' => $capital['id'], 'line_number' => 2, 'debit' => '0.000000', 'credit' => '1000.000000'],
            ],
        ])->assertSuccessful()->json('data');

        $this->tenantPostJson($tenantId, '/api/v1/finance/journals/'.$journal['id'].'/post', [
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
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) OrganizationUnitFixture::create([
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

        return $this->tenantPostJson($tenantId, '/api/v1/items', [
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
    ): void {
        $now = now();
        $userId = (int) TenantUserFixture::create([
            'tenant_id' => $tenantId,
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
            'name' => UserSystemRole::SUPER_ADMIN_NAME,
            'guard_name' => UserGuard::TENANT_API,
            'system_key' => UserSystemRole::SUPER_ADMIN,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($permissions as $permission) {
            $permissionId = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $permission,
                'guard_name' => UserGuard::TENANT_API,
                'module' => $module,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = app(TenantExecutionContextInterface::class)->runForTenant(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user);
    }

    private function createItemDirect(int $tenantId, int $organizationUnitId, int $uomId, string $code): array
    {
        $item = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(ItemCreationService::class)->create(new CreateItemData(
                tenantId: $tenantId,
                code: $code,
                name: $code,
                itemType: ItemType::Stock,
                trackingType: TrackingType::None,
                costingMethod: CostingMethod::Fifo,
                baseUomId: $uomId,
                organizationUnitId: $organizationUnitId,
                isStockable: true,
            )),
        );

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
            'direction' => 'outbound',
            'invoice_date' => '2026-06-06',
            'customer_id' => $customerId,
            'lines' => [[
                'description' => 'API invoice line',
                'quantity' => '2.000000',
                'unit_price' => '50.000000',
            ]],
        ];
    }

    private function createPostedInvoice(int $tenantId, int $organizationUnitId): Invoice
    {
        $payload = $this->invoicePayload($tenantId, $organizationUnitId) + [
            'idempotency_key' => 'invoice-'.Str::uuid()->toString(),
        ];
        $invoice = $this->tenantPostJson($tenantId, '/api/v1/invoices', $payload)
            ->assertSuccessful()->json('data');
        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        $invoice = $this->tenantPostJson($tenantId, '/api/v1/invoices/'.$invoice['id'].'/approve', $scope + [
            'expected_version' => $invoice['row_version'],
        ])->assertSuccessful()->json('data');
        $this->tenantPostJson($tenantId, '/api/v1/invoices/'.$invoice['id'].'/post', $scope + [
            'expected_version' => $invoice['row_version'],
        ])->assertSuccessful();

        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => Invoice::query()->findOrFail($invoice['id']),
        );
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
            'requires_instrument_details' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentFinanceContext(int $tenantId, int $organizationUnitId): void
    {
        FinancePostingFixture::seedCustomerPaymentProfiles($tenantId, $organizationUnitId);
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
        return $this->tenantPostJson($tenantId, '/api/v1/finance/accounts', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_type_id' => $accountTypeId,
            'code' => $code,
            'name' => $name,
            'normal_balance' => $normalBalance,
        ])->assertSuccessful()->json('data');
    }
}
