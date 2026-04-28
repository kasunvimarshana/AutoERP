<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class EventReplayGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_finance_posting_listeners_keep_replay_guards_for_retry_safety(): void
    {
        $requiredGuards = [
            'app/Modules/Finance/Infrastructure/Listeners/HandlePurchasePaymentRecorded.php' => [
                'artifactsAlreadyPosted',
                "'purchase_payment'",
                'HandlePurchasePaymentRecorded: replay detected; finance artifacts already exist, skipping',
                'incomplete finance artifacts',
            ],
            'app/Modules/Finance/Infrastructure/Listeners/HandleSalesPaymentRecorded.php' => [
                'artifactsAlreadyPosted',
                "'sales_payment'",
                'HandleSalesPaymentRecorded: replay detected; finance artifacts already exist, skipping',
                'incomplete finance artifacts',
            ],
            'app/Modules/Finance/Infrastructure/Listeners/HandlePurchaseReturnPosted.php' => [
                'artifactsAlreadyPosted',
                "'purchase_return'",
                'HandlePurchaseReturnPosted: replay detected; finance artifacts already exist, skipping',
                'incomplete finance artifacts',
            ],
            'app/Modules/Finance/Infrastructure/Listeners/HandleSalesReturnReceived.php' => [
                'artifactsAlreadyPosted',
                "'sales_return'",
                'HandleSalesReturnReceived: replay detected; finance artifacts already exist, skipping',
                'incomplete finance artifacts',
            ],
            'app/Modules/Finance/Infrastructure/Listeners/HandlePurchaseInvoiceApproved.php' => [
                'artifactsAlreadyPosted',
                "'purchase_invoice'",
                'HandlePurchaseInvoiceApproved: replay detected; finance artifacts already exist, skipping',
                'incomplete finance artifacts',
            ],
        ];

        foreach ($requiredGuards as $relativePath => $needles) {
            $contents = $this->readSource($relativePath);

            foreach ($needles as $needle) {
                $this->assertStringContainsString(
                    $needle,
                    $contents,
                    'Missing replay guard contract in: '.$relativePath.' -> '.$needle
                );
            }
        }
    }

    public function test_finance_journal_only_listeners_keep_replay_guards_for_retry_safety(): void
    {
        $requiredGuards = [
            'app/Modules/Finance/Infrastructure/Listeners/HandleSalesInvoicePosted.php' => [
                'journalAlreadyPosted',
                "'sales_invoice'",
                'HandleSalesInvoicePosted: replay detected; journal entry already exists, skipping',
                'missing journal artifact',
            ],
            'app/Modules/Finance/Infrastructure/Listeners/HandlePayrollRunApproved.php' => [
                'journalAlreadyPosted',
                "'payroll_run'",
                'HandlePayrollRunApproved: replay detected; journal entry already exists, skipping',
                'missing journal artifact',
            ],
            'app/Modules/Finance/Infrastructure/Listeners/HandleCycleCountCompleted.php' => [
                'journalAlreadyPosted',
                "'cycle_count'",
                'HandleCycleCountCompleted: replay detected; journal entry already exists, skipping',
                'missing journal artifact',
            ],
            'app/Modules/Finance/Infrastructure/Listeners/HandleStockAdjustmentRecorded.php' => [
                'journalAlreadyPosted',
                "'stock_movement'",
                'HandleStockAdjustmentRecorded: replay detected; journal entry already exists, skipping',
                'missing journal artifact',
            ],
        ];

        foreach ($requiredGuards as $relativePath => $needles) {
            $contents = $this->readSource($relativePath);

            foreach ($needles as $needle) {
                $this->assertStringContainsString(
                    $needle,
                    $contents,
                    'Missing replay guard contract in: '.$relativePath.' -> '.$needle
                );
            }
        }
    }

    private function readSource(string $relativePath): string
    {
        $fullPath = $this->repoRoot.'/'.$relativePath;
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            $this->fail('Unable to read source file: '.$relativePath);
        }

        return $contents;
    }
}
