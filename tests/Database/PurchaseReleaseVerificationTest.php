<?php

declare(strict_types=1);

namespace Tests\Database;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Services\PurchaseDebitNoteService;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\Support\CurrencyFixture;
use Tests\TestCase;

final class PurchaseReleaseVerificationTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        DB::disconnect();
        parent::tearDown();
    }

    public function test_competing_requests_for_one_note_reject_the_stale_version_after_the_invoice_lock_releases(): void
    {
        [$tenant, $invoice, $note] = $this->fixture();
        $output = $this->compete($tenant, $invoice, $note, $note);
        $this->assertStringContainsString('"expected_version"', $output);
        $this->assertStringContainsString('Purchase document was changed by another request.', $output);
        $this->assertConservation($tenant, $invoice, $note);
    }

    public function test_two_notes_cannot_overallocate_the_same_invoice_under_real_lock_contention(): void
    {
        [$tenant, $invoice, $first] = $this->fixture();
        $secondRow = (array) DB::table('purchase_debit_notes')->where('id', $first)->first();
        unset($secondRow['id']);
        $secondRow['debit_note_number'] = 'PDN-CONTENTION-SECOND';
        $second = (int) DB::table('purchase_debit_notes')->insertGetId($secondRow);
        $output = $this->compete($tenant, $invoice, $first, $second);
        $this->assertStringContainsString('invariant', $output);
        $this->assertStringContainsString('invoice balance', strtolower($output));
        $this->assertConservation($tenant, $invoice, $first);
        $this->assertSame('0.000000', DB::table('purchase_debit_notes')->where('id', $second)->value('allocated_amount'));
        $this->assertSame(1, (int) DB::table('purchase_debit_notes')->where('id', $second)->value('row_version'));
    }

    public function test_repeated_micro_credit_allocations_preserve_exact_previous_amounts(): void
    {
        [$tenant, $invoiceId, $noteId] = $this->fixture();

        $this->withTenantExecutionContext($tenant, function () use ($invoiceId, $noteId): void {
            $service = app(PurchaseDebitNoteService::class);
            foreach ([1, 2, 3] as $expectedVersion) {
                $service->allocate(
                    PurchaseDebitNote::query()->findOrFail($noteId),
                    Invoice::query()->findOrFail($invoiceId),
                    '0.000001',
                    $expectedVersion,
                );
            }
        });

        $allocations = DB::table('invoice_credit_allocations')
            ->where('tenant_id', $tenant)
            ->where('credit_source_type', 'purchase_debit_note')
            ->where('credit_source_id', $noteId)
            ->orderBy('id')->get();

        $this->assertCount(3, $allocations);
        $this->assertSame(
            ['0.000000', '0.000001', '0.000002'],
            $allocations->pluck('previously_allocated_amount')->all(),
        );
        $this->assertSame(
            ['9.999999', '9.999998', '9.999997'],
            $allocations->pluck('remaining_invoice_balance')->all(),
        );
        $this->assertSame('0.000003', DB::table('purchase_debit_notes')
            ->where('id', $noteId)->value('allocated_amount'));
        $this->assertSame('9.999997', DB::table('purchase_debit_notes')
            ->where('id', $noteId)->value('remaining_amount'));
        $this->assertSame('0.000003', DB::table('invoice_balances')
            ->where('invoice_id', $invoiceId)->value('credit_allocated_amount'));
        $this->assertSame('9.999997', DB::table('invoice_balances')
            ->where('invoice_id', $invoiceId)->value('remaining_amount'));
    }

    public function test_populated_currency_upgrade_preserves_amounts_and_does_not_guess_historical_currency(): void
    {
        [$tenant, $invoice, $note] = $this->fixture();
        $this->withTenantExecutionContext($tenant, function () use ($invoice, $note): void {
            app(PurchaseDebitNoteService::class)->allocate(
                PurchaseDebitNote::query()->findOrFail($note),
                Invoice::query()->findOrFail($invoice), '8.000000', 1,
            );
        });
        $before = (array) DB::table('purchase_debit_notes')->where('id', $note)->first();
        $allocations = DB::table('invoice_credit_allocations')->where('tenant_id', $tenant)->get()->toJson();
        $auditBefore = $this->audit();
        $this->assertSame(0, (int) $auditBefore[0][0]->currency_unresolved_count);
        $this->assertSame([], $auditBefore[2]);
        $this->assertSame([], $auditBefore[3]);
        $migration = require base_path('app/Modules/Purchase/Database/UpgradeMigrations/2026_10_13_170014_add_currency_id_to_purchase_debit_notes.php');
        $migration->down();
        $migration->up();
        $migration->up();
        $after = (array) DB::table('purchase_debit_notes')->where('id', $note)->first();
        $this->assertNull($after['currency_id']);
        unset($before['currency_id'], $after['currency_id']);
        $this->assertEquals($before, $after);
        $this->assertSame($allocations, DB::table('invoice_credit_allocations')->where('tenant_id', $tenant)->get()->toJson());
        $auditAfter = $this->audit();
        $this->assertSame(1, (int) $auditAfter[0][0]->unresolved_with_allocations_count);
        $this->assertCount(1, $auditAfter[2]);
        $this->assertSame('unresolved_currency', $auditAfter[2][0]->review_reason);
        $this->assertSame([], $auditAfter[3]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('verified currencies');
        $this->withTenantExecutionContext($tenant, fn () => app(PurchaseDebitNoteService::class)->allocate(
            PurchaseDebitNote::query()->findOrFail($note),
            Invoice::query()->findOrFail($invoice), '1.000000', 2,
        ));
    }

    private function audit(): array
    {
        $sql = file_get_contents(base_path('docs/audits/purchase-debit-note-currency-reconciliation.sql'));
        $queries = array_values(array_filter(array_map('trim', explode(';', preg_replace('/^--.*$/m', '', $sql)))));
        $this->assertCount(5, $queries);

        return array_map(fn (string $query) => DB::select($query), $queries);
    }

    private function compete(int $tenant, int $invoice, int $firstNote, int $secondNote): string
    {
        $this->assertSame('mysql', DB::connection()->getDriverName(), 'Execute this suite with the guarded phpunit.mysql.xml profile.');
        $input = new InputStream;
        $processEnv = array_filter([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => (string) getenv('DB_CONNECTION'),
            'DB_HOST' => (string) getenv('DB_HOST'),
            'DB_PORT' => (string) getenv('DB_PORT'),
            'DB_DATABASE' => (string) getenv('DB_DATABASE'),
            'DB_USERNAME' => (string) getenv('DB_USERNAME'),
            'DB_PASSWORD' => (string) getenv('DB_PASSWORD'),
            'DB_URL' => '',
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: null,
            'PATH' => getenv('PATH') ?: null,
        ], static fn ($val): bool => $val !== null);

        $first = new Process([PHP_BINARY, base_path('tests/Support/purchase-allocation-worker.php'), (string) $tenant, (string) $invoice, (string) $firstNote, 'holder'], base_path(), $processEnv, $input, 120);
        $second = new Process([PHP_BINARY, base_path('tests/Support/purchase-allocation-worker.php'), (string) $tenant, (string) $invoice, (string) $secondNote, 'contender'], base_path(), $processEnv, null, 120);
        try {
            $first->start();
            $this->assertTrue($first->waitUntil(fn () => str_contains($first->getOutput(), 'locked')), $first->getErrorOutput());
            $second->start();
            $this->assertTrue($second->waitUntil(fn () => str_contains($second->getOutput(), 'attempting:')), $second->getErrorOutput());
            preg_match('/attempting:(\d+)/', $second->getOutput(), $match);
            $this->assertArrayHasKey(1, $match);
            $deadline = microtime(true) + 15;
            $waiting = false;
            $versionOutput = (string) (DB::selectOne('SELECT VERSION() AS ver')?->ver ?? '');
            $isMariaDb = str_contains(strtolower($versionOutput), 'mariadb');
            do {
                if ($isMariaDb) {
                    $waiting = DB::selectOne(
                        'SELECT COUNT(*) AS waits FROM information_schema.INNODB_LOCK_WAITS w JOIN information_schema.INNODB_TRX t ON t.trx_id = w.requesting_trx_id WHERE t.trx_mysql_thread_id = ?',
                        [(int) $match[1]],
                    )->waits > 0;
                } else {
                    $waiting = DB::selectOne(
                        'SELECT COUNT(*) AS waits FROM performance_schema.data_lock_waits w JOIN performance_schema.threads t ON t.THREAD_ID = w.REQUESTING_THREAD_ID WHERE t.PROCESSLIST_ID = ?',
                        [(int) $match[1]],
                    )->waits > 0;
                }
                if (! $waiting) {
                    usleep(10000);
                }
            } while (! $waiting && microtime(true) < $deadline);
            $this->assertTrue($waiting, 'The contender must be observed waiting on a real InnoDB row lock.');
            $input->write("release\n");
            $input->close();
            $first->wait();
            $second->wait();
            $this->assertSame(0, $first->getExitCode(), $first->getErrorOutput().$first->getOutput());
            $this->assertContains($second->getExitCode(), [10, 11], $second->getErrorOutput().$second->getOutput());

            return $second->getOutput();
        } finally {
            $input->close();
            $first->stop();
            $second->stop();
        }
    }

    private function assertConservation(int $tenant, int $invoice, int $note): void
    {
        $this->assertSame(2, (int) DB::table('purchase_debit_notes')->where('id', $note)->value('row_version'));
        $this->assertSame('8.000000', DB::table('purchase_debit_notes')->where('id', $note)->value('allocated_amount'));
        $this->assertSame('2.000000', DB::table('purchase_debit_notes')->where('id', $note)->value('remaining_amount'));
        $this->assertSame('2.000000', DB::table('invoice_balances')->where('invoice_id', $invoice)->value('remaining_amount'));
        $this->assertSame('8.000000', DB::table('invoice_balances')->where('invoice_id', $invoice)->value('credit_allocated_amount'));
        $this->assertSame(1, DB::table('invoice_credit_allocations')->where('tenant_id', $tenant)->count());
    }

    private function fixture(): array
    {
        $tenant = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'code' => 'CONTEND', 'name' => 'Concurrency fixture', 'slug' => 'contention-fixture',
            'status' => 'active', 'status_changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $currency = CurrencyFixture::create();
        $supplier = (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenant, 'supplier_number' => 'SUP-CONTEND', 'code' => 'SUP-CONTEND', 'name' => 'Test supplier',
            'supplier_type' => 'company', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $invoice = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenant, 'invoice_number' => 'INV-CONTEND', 'invoice_type' => 'purchase', 'direction' => 'inbound',
            'party_type' => 'supplier', 'party_id' => $supplier, 'invoice_date' => '2026-10-14', 'status' => 'posted',
            'currency_id' => $currency, 'currency_code_snapshot' => DB::table('currencies')->where('id', $currency)->value('code'),
            'grand_total' => '10.000000', 'balance_due' => '10.000000', 'exchange_rate' => '1.000000', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoice_balances')->insert([
            'tenant_id' => $tenant, 'invoice_id' => $invoice, 'invoice_total' => '10.000000', 'remaining_amount' => '10.000000',
            'status' => 'unpaid', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $note = (int) DB::table('purchase_debit_notes')->insertGetId([
            'tenant_id' => $tenant, 'supplier_type' => 'supplier', 'supplier_id' => $supplier, 'currency_id' => $currency,
            'debit_note_number' => 'PDN-CONTEND', 'debit_note_date' => '2026-10-14', 'status' => 'posted',
            'amount' => '10.000000', 'allocated_amount' => '0.000000', 'remaining_amount' => '10.000000',
            'reason' => 'Isolated concurrency fixture', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$tenant, $invoice, $note];
    }
}
