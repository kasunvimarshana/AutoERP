<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceFinanceIntegrationService;
use Tests\TestCase;

final class InvoiceFinanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_prepares_finance_posting_request_without_posting(): void
    {
        [$tenantId] = $this->createChart();
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-FIN-PREP',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Invoice finance preparation',
                    quantity: '1.000000',
                    unitPrice: '1000.000000',
                ),
            ],
        )));

        $request = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceFinanceIntegrationService::class)->preparePostingRequest(
            (int) $invoice->getKey(),
            '2026-06-06',
            [
                new FinancePostingLine(null, 'Cash', debit: '1000.000000', profileKey: 'cash'),
                new FinancePostingLine(null, 'Capital', credit: '1000.000000', profileKey: 'capital'),
            ],
            'invoice_posting',
        ));

        $this->assertSame('invoice', $request->source->sourceType);
        $this->assertSame((int) $invoice->getKey(), $request->source->sourceId);
        $this->assertSame($tenantId, $request->source->tenantId);
        $this->assertSame('invoice', $request->source->sourceModule);
        $this->assertSame('INV-FIN-PREP', $request->source->sourceNumber);
        $this->assertCount(2, $request->lines);

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceFinanceIntegrationService::class)->validatePostingRequest($request));
        $this->assertDatabaseCount('finance_journal_entries', 0);
    }

    /**
     * @return array{0: int}
     */
    private function createChart(): array
    {
        $tenantId = $this->createTenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $assetType = $this->createAccountType($tenantId, 'ASSET', NormalBalance::Debit);
            $equityType = $this->createAccountType($tenantId, 'EQUITY', NormalBalance::Credit);

            app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
                tenantId: $tenantId,
                accountTypeId: (int) $assetType->getKey(),
                code: '1010',
                name: 'Cash',
                normalBalance: NormalBalance::Debit,
            ));
            app(ChartOfAccountsService::class)->createAccount(new CreateAccountData(
                tenantId: $tenantId,
                accountTypeId: (int) $equityType->getKey(),
                code: '3000',
                name: 'Capital',
                normalBalance: NormalBalance::Credit,
            ));

            $cashRoleId = $this->createAccountRole($tenantId, 'cash', 'Cash');
            $capitalRoleId = $this->createAccountRole($tenantId, 'capital', 'Capital');
            $now = now();
            DB::table('finance_account_assignments')->insert([
                [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'account_role_id' => $cashRoleId,
                    'account_id' => (int) DB::table('finance_accounts')->where('tenant_id', $tenantId)->where('code', '1010')->value('id'),
                    'effective_from' => '2026-01-01',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'account_role_id' => $capitalRoleId,
                    'account_id' => (int) DB::table('finance_accounts')->where('tenant_id', $tenantId)->where('code', '3000')->value('id'),
                    'effective_from' => '2026-01-01',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $postingProfileId = (int) DB::table('finance_posting_profiles')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'code' => 'invoice_posting',
                'name' => 'Invoice Posting',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('finance_posting_profile_rules')->insert([
                [
                    'tenant_id' => $tenantId,
                    'posting_profile_id' => $postingProfileId,
                    'line_key' => 'cash',
                    'account_role_id' => $cashRoleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'tenant_id' => $tenantId,
                    'posting_profile_id' => $postingProfileId,
                    'line_key' => 'capital',
                    'account_role_id' => $capitalRoleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        });

        return [$tenantId];
    }

    private function createAccountType(int $tenantId, string $code, NormalBalance $normalBalance): FinanceAccountType
    {
        return FinanceAccountType::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'normal_balance' => $normalBalance->value,
            'statement_type' => StatementType::BalanceSheet->value,
            'is_active' => true,
        ]);
    }

    private function createAccountRole(int $tenantId, string $code, string $name): int
    {
        return (int) DB::table('finance_account_roles')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-IFI-'.$suffix,
            'name' => 'Invoice Finance Integration '.$suffix,
            'slug' => 'invoice-finance-integration-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);
    }
}
