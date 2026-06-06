<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\DTOs\Integration\PostingLineData;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentFinanceIntegrationService;
use Tests\TestCase;

final class PaymentFinanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_prepares_finance_posting_request_without_posting(): void
    {
        [$tenantId] = $this->createChart();
        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-FIN-PREP',
            lines: [
                new PaymentLineData(amount: '1000.000000'),
            ],
        ));

        $service = app(PaymentFinanceIntegrationService::class);
        $paymentRequest = $service->preparePaymentPostingRequest((int) $payment->getKey(), [
            new PostingLineData('1010', 'Cash', debit: '1000.000000'),
            new PostingLineData('3000', 'Capital', credit: '1000.000000'),
        ]);
        $financeRequest = $service->toFinancePostingRequest($paymentRequest);

        $this->assertSame((int) $payment->getKey(), $paymentRequest->paymentId);
        $this->assertSame('payment', $financeRequest->source->sourceType);
        $this->assertSame($tenantId, $financeRequest->source->tenantId);
        $this->assertCount(2, $financeRequest->lines);

        $service->validatePostingRequest($financeRequest);
        $this->assertDatabaseCount('finance_journal_entries', 0);
    }

    /**
     * @return array{0: int}
     */
    private function createChart(): array
    {
        $tenantId = $this->createTenant();
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

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-PFI-'.$suffix,
            'name' => 'Payment Finance Integration '.$suffix,
            'slug' => 'payment-finance-integration-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
