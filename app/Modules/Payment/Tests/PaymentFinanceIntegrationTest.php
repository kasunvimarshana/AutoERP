<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Payment\Constants\PaymentPostingMetadata;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\DTOs\PaymentPostingContext;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingRole;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentFinanceIntegrationService;
use Tests\TestCase;

final class PaymentFinanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_prepares_finance_posting_request_without_posting(): void
    {
        [$tenantId] = $this->createChart();
        $payment = $this->withTenantExecutionContext($tenantId, fn () => app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Manual,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            lines: [
                new PaymentLineData(
                    amount: '1000.000000',
                    paymentMethodId: (int) $this->paymentMethod($tenantId)->getKey(),
                ),
            ],
            metadata: [
                PaymentPostingMetadata::PROFILE_CODE => 'payment_posting',
                PaymentPostingMetadata::COUNTERPARTY_ROLE => PaymentPostingRole::Payable->value,
            ],
        )));

        $service = app(PaymentFinanceIntegrationService::class);
        $paymentRequest = $this->withTenantExecutionContext($tenantId, fn () => $service->preparePaymentPostingRequest((int) $payment->getKey(), [
            new FinancePostingLine(null, 'Cash', debit: '1000.000000', profileKey: 'cash'),
            new FinancePostingLine(null, 'Capital', credit: '1000.000000', profileKey: 'capital'),
        ], 'payment_posting'));
        $financeRequest = $this->withTenantExecutionContext($tenantId, fn () => $service->toFinancePostingRequest($paymentRequest));
        $paymentContext = $this->withTenantExecutionContext($tenantId, fn () => $service->toPaymentPostingContext($paymentRequest));

        $this->assertSame((int) $payment->getKey(), $paymentRequest->paymentId);
        $this->assertInstanceOf(PaymentPostingContext::class, $paymentContext);
        $this->assertSame('payment', $paymentContext->source->sourceType);
        $this->assertSame('payment', $financeRequest->source->sourceType);
        $this->assertSame($tenantId, $financeRequest->source->tenantId);
        $this->assertSame('payment', $financeRequest->source->sourceModule);
        $this->assertNotSame('', trim((string) $payment->payment_number));
        $this->assertSame((string) $payment->payment_number, $financeRequest->source->sourceNumber);
        $this->assertCount(2, $financeRequest->lines);

        $this->withTenantExecutionContext($tenantId, fn () => $service->validatePostingRequest($financeRequest));
        $this->assertDatabaseCount('finance_journal_entries', 0);
    }

    /** @return array{0: int} */
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
                'code' => 'payment_posting',
                'name' => 'Payment Posting',
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
            'code' => 'TEN-PFI-'.$suffix,
            'name' => 'Payment Finance Integration '.$suffix,
            'slug' => 'payment-finance-integration-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentMethod(int $tenantId): PaymentMethod
    {
        return $this->withTenantExecutionContext($tenantId, fn () => PaymentMethod::query()->create([
            'tenant_id' => $tenantId,
            'code' => 'CASH',
            'name' => 'Cash',
            'method_type' => PaymentMethodType::Cash,
            'direction_allowed' => 'both',
            'requires_reference' => false,
            'requires_instrument_details' => false,
            'is_active' => true,
        ]));
    }
}
