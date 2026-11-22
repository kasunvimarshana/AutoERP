<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Reporting\Services\CashierReportService;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Models\UserModel;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class CashierReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_report_computes_exact_drawer_cash_and_tender_breakdowns(): void
    {
        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CR-'.Str::upper(Str::random(6)),
            'name' => 'Cashier report tenant',
            'slug' => 'cashier-report-'.Str::lower(Str::random(6)),
            'status' => 'active',
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cashierAliceId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'first_name' => 'Alice',
            'last_name' => 'Teller',
            'email' => 'alice@cashier.test',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cashierBobId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'first_name' => 'Bob',
            'last_name' => 'Teller',
            'email' => 'bob@cashier.test',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cashMethodId = (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'CASH',
            'name' => 'Cash Currency',
            'method_type' => 'cash',
            'direction_allowed' => 'both',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cardMethodId = (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'CARD',
            'name' => 'Visa / Mastercard',
            'method_type' => 'card',
            'direction_allowed' => 'inbound',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 1. Inbound payment by Alice: Total 100.00 (Split: 60 Cash + 40 Card)
        $p1 = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'payment_number' => 'RCP-2026-0001',
            'payment_type' => 'customer_receipt',
            'direction' => PaymentDirection::Inbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'posting_status' => 'posted',
            'payment_date' => '2026-11-23',
            'party_name_snapshot' => 'Customer John',
            'total_amount' => '100.000000',
            'allocated_amount' => '100.000000',
            'created_by' => $cashierAliceId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'payment_id' => $p1,
            'line_number' => 1,
            'payment_method_id' => $cashMethodId,
            'payment_method_code_snapshot' => 'CASH',
            'payment_method_name_snapshot' => 'Cash Currency',
            'payment_method_type_snapshot' => 'cash',
            'amount' => '60.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'payment_id' => $p1,
            'line_number' => 2,
            'payment_method_id' => $cardMethodId,
            'payment_method_code_snapshot' => 'CARD',
            'payment_method_name_snapshot' => 'Visa / Mastercard',
            'payment_method_type_snapshot' => 'card',
            'amount' => '40.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Inbound payment by Bob: Total 50.00 (100% Cash)
        $p2 = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'payment_number' => 'RCP-2026-0002',
            'payment_type' => 'customer_receipt',
            'direction' => PaymentDirection::Inbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'posting_status' => 'posted',
            'payment_date' => '2026-11-23',
            'party_name_snapshot' => 'Customer Jane',
            'total_amount' => '50.000000',
            'allocated_amount' => '50.000000',
            'created_by' => $cashierBobId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'payment_id' => $p2,
            'line_number' => 1,
            'payment_method_id' => $cashMethodId,
            'payment_method_code_snapshot' => 'CASH',
            'payment_method_name_snapshot' => 'Cash Currency',
            'payment_method_type_snapshot' => 'cash',
            'amount' => '50.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 3. Outbound payment (Refund) by Alice: Total 20.00 (100% Cash Refund from drawer)
        $p3 = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'payment_number' => 'REF-2026-0001',
            'payment_type' => 'refund',
            'direction' => PaymentDirection::Outbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'posting_status' => 'posted',
            'payment_date' => '2026-11-23',
            'party_name_snapshot' => 'Customer John',
            'total_amount' => '20.000000',
            'allocated_amount' => '0.000000',
            'created_by' => $cashierAliceId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'payment_id' => $p3,
            'line_number' => 1,
            'payment_method_id' => $cashMethodId,
            'payment_method_code_snapshot' => 'CASH',
            'payment_method_name_snapshot' => 'Cash Currency',
            'payment_method_type_snapshot' => 'cash',
            'amount' => '20.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $service = app(CashierReportService::class);

        // Test tenant-wide Cashier Report
        $report = $service->run($tenantId, null, [
            'date_from' => '2026-11-23',
            'date_to' => '2026-11-23',
            'direction' => 'all',
            'document_status' => 'approved',
        ]);

        // Assert overall summary
        // Inflow = 100 (p1) + 50 (p2) = 150.00
        // Outflow = 20 (p3) = 20.00
        // Net Collections = 130.00
        // Cash Inflow = 60 (p1) + 50 (p2) = 110.00
        // Cash Outflow = 20 (p3) = 20.00
        // Net Cash in Drawer = 110 - 20 = 90.00!
        // Non-Cash Total = 130 - 90 = 40.00 (Card from p1)!
        self::assertSame('150.00', $report['summary']['total_inflow']);
        self::assertSame('20.00', $report['summary']['total_outflow']);
        self::assertSame('130.00', $report['summary']['net_collections']);
        self::assertSame('110.00', $report['summary']['cash_inflow']);
        self::assertSame('20.00', $report['summary']['cash_outflow']);
        self::assertSame('90.00', $report['summary']['net_cash_in_drawer']);
        self::assertSame('40.00', $report['summary']['non_cash_total']);
        self::assertSame(3, $report['summary']['total_transactions']);
        self::assertSame(2, $report['summary']['inbound_count']);
        self::assertSame(1, $report['summary']['outbound_count']);

        // Assert payment methods breakdown
        self::assertCount(2, $report['payment_methods']);
        $methodMap = collect($report['payment_methods'])->keyBy('key');
        self::assertSame('110.00', $methodMap['CASH']['inflow']);
        self::assertSame('20.00', $methodMap['CASH']['outflow']);
        self::assertSame('90.00', $methodMap['CASH']['net']);
        self::assertSame('40.00', $methodMap['CARD']['inflow']);
        self::assertSame('0.00', $methodMap['CARD']['outflow']);
        self::assertSame('40.00', $methodMap['CARD']['net']);

        // Assert cashier breakdown
        self::assertCount(2, $report['cashiers']);
        $cashierMap = collect($report['cashiers'])->keyBy('cashier_id');
        // Alice: Inflow 100, Outflow 20, Net 80. Cash In 60, Cash Out 20 => Cash Net 40, Non-Cash Net 40. Tx 2.
        self::assertSame('100.00', $cashierMap[$cashierAliceId]['inflow']);
        self::assertSame('20.00', $cashierMap[$cashierAliceId]['outflow']);
        self::assertSame('80.00', $cashierMap[$cashierAliceId]['net']);
        self::assertSame('40.00', $cashierMap[$cashierAliceId]['cash_net']);
        self::assertSame('40.00', $cashierMap[$cashierAliceId]['non_cash_net']);
        self::assertSame(2, $cashierMap[$cashierAliceId]['transaction_count']);

        // Bob: Inflow 50, Outflow 0, Net 50. Cash Net 50, Non-Cash Net 0. Tx 1.
        self::assertSame('50.00', $cashierMap[$cashierBobId]['inflow']);
        self::assertSame('0.00', $cashierMap[$cashierBobId]['outflow']);
        self::assertSame('50.00', $cashierMap[$cashierBobId]['net']);
        self::assertSame('50.00', $cashierMap[$cashierBobId]['cash_net']);
        self::assertSame('0.00', $cashierMap[$cashierBobId]['non_cash_net']);
        self::assertSame(1, $cashierMap[$cashierBobId]['transaction_count']);

        // Assert paginated transactions
        self::assertSame(3, $report['transactions']['meta']['total']);
        self::assertCount(3, $report['transactions']['data']);

        // Test filtering by Cashier Alice only
        $aliceReport = $service->run($tenantId, null, [
            'date_from' => '2026-11-23',
            'date_to' => '2026-11-23',
            'cashier_user_id' => $cashierAliceId,
        ]);
        self::assertSame('100.00', $aliceReport['summary']['total_inflow']);
        self::assertSame('20.00', $aliceReport['summary']['total_outflow']);
        self::assertSame('40.00', $aliceReport['summary']['net_cash_in_drawer']);
        self::assertSame(2, $aliceReport['transactions']['meta']['total']);

        // Test lookup endpoint
        $lookup = $service->cashiersLookup($tenantId, null);
        self::assertCount(2, $lookup);
        self::assertSame('Alice Teller', $lookup[0]['name']);
        self::assertSame('Bob Teller', $lookup[1]['name']);

        // Test export rows
        $export = $service->exportRows($tenantId, null, [
            'date_from' => '2026-11-23',
            'date_to' => '2026-11-23',
        ]);
        // 3 transaction rows + 3 summary rows (Total Inflow, Total Outflow, Net Collections)
        self::assertCount(6, $export);
        self::assertSame('RCP-2026-0001', $export[0]['payment_number']);
        self::assertSame('TOTAL INFLOW', $export[3]['payment_date']);
    }

    public function test_cashier_report_api_endpoints_and_exports(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();

        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CR-API-'.Str::upper(Str::random(6)),
            'name' => 'Cashier report API tenant',
            'slug' => 'cashier-report-api-'.Str::lower(Str::random(6)),
            'status' => 'active',
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cashierId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Emma',
            'last_name' => 'Cashier',
            'email' => 'emma@cashier.test',
            'status' => 'active',
        ]);

        $guard = UserGuard::TENANT_API;
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => UserSystemRole::SUPER_ADMIN_NAME,
            'active_name_key' => mb_strtolower(UserSystemRole::SUPER_ADMIN_NAME),
            'guard_name' => $guard,
            'system_key' => UserSystemRole::SUPER_ADMIN,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (ReportingAuthorizationService::descriptions() as $permName => $permDesc) {
            $pId = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $permName,
                'guard_name' => $guard,
                'module' => 'Reporting',
                'description' => $permDesc,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'role_id' => $roleId,
                'permission_id' => $pId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $cashierId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cashMethodId = (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'CASH',
            'name' => 'Cash',
            'method_type' => 'cash',
            'direction_allowed' => 'both',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'payment_number' => 'RCP-API-001',
            'payment_type' => 'customer_receipt',
            'direction' => PaymentDirection::Inbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'posting_status' => 'posted',
            'payment_date' => '2026-11-23',
            'party_name_snapshot' => 'Counter Customer',
            'total_amount' => '75.000000',
            'allocated_amount' => '75.000000',
            'created_by' => $cashierId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'payment_id' => $pId,
            'line_number' => 1,
            'payment_method_id' => $cashMethodId,
            'payment_method_code_snapshot' => 'CASH',
            'payment_method_name_snapshot' => 'Cash',
            'payment_method_type_snapshot' => 'cash',
            'amount' => '75.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($cashierId),
        );
        $this->actingAs($user, $guard);

        // 1. Test GET api/v1/reports/cashier
        $response = $this->withTenantRequestContext(
            $tenantId,
            $cashierId,
            fn () => $this->getJson('/api/v1/reports/cashier?'.http_build_query([
                'tenant_id' => $tenantId,
                'date_from' => '2026-11-23',
                'date_to' => '2026-11-23',
            ])),
        );
        $response->assertOk()
            ->assertJsonPath('data.period.date_from', '2026-11-23')
            ->assertJsonPath('data.summary.total_inflow', '75.00')
            ->assertJsonPath('data.summary.net_cash_in_drawer', '75.00')
            ->assertJsonPath('data.transactions.meta.total', 1);

        // 2. Test GET api/v1/reports/cashier/cashiers
        $cashiersResponse = $this->withTenantRequestContext(
            $tenantId,
            $cashierId,
            fn () => $this->getJson('/api/v1/reports/cashier/cashiers?'.http_build_query([
                'tenant_id' => $tenantId,
                'date_from' => '2026-11-23',
                'date_to' => '2026-11-23',
            ])),
        );
        $cashiersResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Emma Cashier');

        // 3. Test GET api/v1/reports/cashier/export/csv
        $csvResponse = $this->withTenantRequestContext(
            $tenantId,
            $cashierId,
            fn () => $this->get('/api/v1/reports/cashier/export/csv?'.http_build_query([
                'tenant_id' => $tenantId,
                'date_from' => '2026-11-23',
                'date_to' => '2026-11-23',
            ])),
        );
        $csvResponse->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // 4. Test GET api/v1/reports/cashier/export/html
        $htmlResponse = $this->withTenantRequestContext(
            $tenantId,
            $cashierId,
            fn () => $this->get('/api/v1/reports/cashier/export/html?'.http_build_query([
                'tenant_id' => $tenantId,
                'date_from' => '2026-11-23',
                'date_to' => '2026-11-23',
            ])),
        );
        $htmlResponse->assertOk();
    }

    public function test_cashier_report_respects_organization_unit_scoping(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();

        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CR-OU-'.Str::upper(Str::random(6)),
            'name' => 'Cashier report OU tenant',
            'slug' => 'cashier-report-ou-'.Str::lower(Str::random(6)),
            'status' => 'active',
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ouType = (int) DB::table('organization_unit_types')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Branch',
            'name_key' => 'branch',
            'level' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ou1 = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'type_id' => $ouType,
            'name' => 'Branch Colombo',
            'code' => 'COLOMBO',
            'path' => '/COLOMBO',
            'path_hash' => hash('sha256', '/COLOMBO'),
            'depth' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ou2 = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'type_id' => $ouType,
            'name' => 'Branch Kandy',
            'code' => 'KANDY',
            'path' => '/KANDY',
            'path_hash' => hash('sha256', '/KANDY'),
            'depth' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cashier = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'David',
            'last_name' => 'Cashier',
            'email' => 'david@cashier.test',
            'status' => 'active',
        ]);

        $cashMethodId = (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'CASH',
            'name' => 'Cash',
            'method_type' => 'cash',
            'direction_allowed' => 'both',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Payment in OU1 (Colombo) = 120.00
        $p1 = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $ou1,
            'payment_number' => 'RCP-COL-001',
            'payment_type' => 'customer_receipt',
            'direction' => PaymentDirection::Inbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'posting_status' => 'posted',
            'payment_date' => '2026-11-23',
            'party_name_snapshot' => 'Colombo Customer',
            'total_amount' => '120.000000',
            'allocated_amount' => '120.000000',
            'created_by' => $cashier,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $ou1,
            'payment_id' => $p1,
            'line_number' => 1,
            'payment_method_id' => $cashMethodId,
            'payment_method_code_snapshot' => 'CASH',
            'payment_method_name_snapshot' => 'Cash',
            'payment_method_type_snapshot' => 'cash',
            'amount' => '120.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Payment in OU2 (Kandy) = 80.00
        $p2 = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $ou2,
            'payment_number' => 'RCP-KAN-001',
            'payment_type' => 'customer_receipt',
            'direction' => PaymentDirection::Inbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'posting_status' => 'posted',
            'payment_date' => '2026-11-23',
            'party_name_snapshot' => 'Kandy Customer',
            'total_amount' => '80.000000',
            'allocated_amount' => '80.000000',
            'created_by' => $cashier,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $ou2,
            'payment_id' => $p2,
            'line_number' => 1,
            'payment_method_id' => $cashMethodId,
            'payment_method_code_snapshot' => 'CASH',
            'payment_method_name_snapshot' => 'Cash',
            'payment_method_type_snapshot' => 'cash',
            'amount' => '80.000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $service = app(CashierReportService::class);

        // OU1 report: exactly 120.00
        $ou1Report = $service->run($tenantId, $ou1, [
            'date_from' => '2026-11-23',
            'date_to' => '2026-11-23',
        ]);
        self::assertSame('120.00', $ou1Report['summary']['total_inflow']);
        self::assertSame('120.00', $ou1Report['summary']['net_cash_in_drawer']);
        self::assertSame(1, $ou1Report['transactions']['meta']['total']);

        // OU2 report: exactly 80.00
        $ou2Report = $service->run($tenantId, $ou2, [
            'date_from' => '2026-11-23',
            'date_to' => '2026-11-23',
        ]);
        self::assertSame('80.00', $ou2Report['summary']['total_inflow']);
        self::assertSame('80.00', $ou2Report['summary']['net_cash_in_drawer']);
        self::assertSame(1, $ou2Report['transactions']['meta']['total']);
    }
}
