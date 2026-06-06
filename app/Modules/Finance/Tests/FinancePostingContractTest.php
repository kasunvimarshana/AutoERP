<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\DTOs\Integration\FinancePostingRequest;
use Modules\Core\DTOs\Integration\PostingLineData;
use Modules\Core\DTOs\Integration\PostingSourceData;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Services\ChartOfAccountsService;
use Tests\TestCase;

final class FinancePostingContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_posting_contract_creates_posts_and_reverses_journals(): void
    {
        [$tenantId] = $this->createChart();

        $contract = app(FinancePostingInterface::class);
        $request = new FinancePostingRequest(
            source: new PostingSourceData('invoice', 1001, $tenantId),
            postingDate: '2026-06-06',
            lines: [
                new PostingLineData('1010', 'Cash', debit: '1000.000000', description: 'Cash debit'),
                new PostingLineData('3000', 'Capital', credit: '1000.000000', description: 'Capital credit'),
            ],
            description: 'Contract posting test',
        );

        $contract->validatePosting($request);
        $draft = $contract->createDraftJournal($request);

        $this->assertSame(JournalStatus::Draft->value, $draft->status);
        $this->assertSame('1000.000000', $draft->totalDebit);
        $this->assertSame('1000.000000', $draft->totalCredit);

        $posted = $contract->postJournal($draft->journalId);
        $this->assertSame(JournalStatus::Posted->value, $posted->status);
        $this->assertSame(2, $posted->ledgerEntryCount);

        $reversal = $contract->reverseJournal($draft->journalId, '2026-06-07');
        $this->assertSame(JournalStatus::Posted->value, $reversal->status);
        $this->assertSame(2, $reversal->ledgerEntryCount);
    }

    public function test_integration_contracts_do_not_create_circular_module_dependencies(): void
    {
        $financeSource = $this->modulePhp('Finance');
        $paymentBoundarySource = $this->modulePhp('Payment', ['Services', 'Validators', 'Models']);
        $invoiceBoundarySource = $this->modulePhp('Invoice', ['Services', 'Validators', 'Models']);

        $this->assertStringNotContainsString('Modules\\Invoice\\', $financeSource);
        $this->assertStringNotContainsString('Modules\\Payment\\', $financeSource);
        $this->assertStringNotContainsString('Modules\\Invoice\\Models\\', $paymentBoundarySource);
        $this->assertStringNotContainsString('Modules\\Invoice\\Services\\InvoiceBalanceService', $paymentBoundarySource);
        $this->assertStringNotContainsString('Modules\\Finance\\Models\\', $invoiceBoundarySource);
        $this->assertStringNotContainsString('Modules\\Finance\\Services\\', $invoiceBoundarySource);
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
            'code' => 'TEN-FPC-'.$suffix,
            'name' => 'Finance Posting Contract '.$suffix,
            'slug' => 'finance-posting-contract-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>|null  $subdirectories
     */
    private function modulePhp(string $module, ?array $subdirectories = null): string
    {
        $roots = $subdirectories ?? ['.'];
        $content = '';

        foreach ($roots as $root) {
            $path = base_path('app/Modules/'.$module.($root === '.' ? '' : '/'.$root));
            if (! is_dir($path)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($files as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $content .= file_get_contents((string) $file->getPathname()) ?: '';
            }
        }

        return $content;
    }
}
