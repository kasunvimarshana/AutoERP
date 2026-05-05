<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class FinanceListenerReplayGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_replay_hardened_finance_listeners_use_shared_replay_trait(): void
    {
        $listenerFiles = [
            'app/Modules/Finance/Infrastructure/Listeners/HandlePurchaseInvoiceApproved.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandleSalesInvoicePosted.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandlePurchasePaymentRecorded.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandleSalesPaymentRecorded.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandlePurchaseReturnPosted.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandleSalesReturnReceived.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandleCycleCountCompleted.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandleStockAdjustmentRecorded.php',
            'app/Modules/Finance/Infrastructure/Listeners/HandlePayrollRunApproved.php',
        ];

        foreach ($listenerFiles as $relativePath) {
            $source = $this->readSource($relativePath);

            $this->assertStringContainsString(
                'use Modules\\Finance\\Infrastructure\\Listeners\\Concerns\\HandlesReplayConflicts;',
                $source,
                'Listener should import shared replay guard trait: '.$relativePath
            );

            $this->assertStringContainsString(
                'use HandlesReplayConflicts;',
                $source,
                'Listener should use shared replay guard trait: '.$relativePath
            );
        }
    }

    public function test_finance_listeners_do_not_reintroduce_local_replay_helper_methods(): void
    {
        $listenerDir = $this->repoRoot.'/app/Modules/Finance/Infrastructure/Listeners';
        $violations = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($listenerDir, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen($this->repoRoot) + 1));
            $source = file_get_contents($fileInfo->getPathname()) ?: '';

            if (str_contains($source, 'private function journalAlreadyPosted(')
                || str_contains($source, 'private function artifactsAlreadyPosted(')
                || str_contains($source, 'private function isReplayConflict(')
            ) {
                $violations[] = $relativePath;
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Replay helper methods must be centralized in HandlesReplayConflicts trait.\n".implode("\n", $violations)
        );
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
