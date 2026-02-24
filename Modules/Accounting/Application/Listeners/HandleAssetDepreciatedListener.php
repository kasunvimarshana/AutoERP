<?php

namespace Modules\Accounting\Application\Listeners;

use Modules\Accounting\Application\UseCases\CreateJournalEntryUseCase;
use Modules\AssetManagement\Domain\Events\AssetDepreciated;

class HandleAssetDepreciatedListener
{
    public function __construct(
        private CreateJournalEntryUseCase $createJournalEntry,
    ) {}

    public function handle(AssetDepreciated $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        if (bccomp($event->depreciationAmount, '0', 8) <= 0) {
            return;
        }

        $assetNote = $event->assetName !== '' ? ' — ' . $event->assetName : '';
        $periodNote = $event->periodLabel !== '' ? ' (' . $event->periodLabel . ')' : '';
        $description = 'Depreciation' . $assetNote . $periodNote;

        try {
            $this->createJournalEntry->execute([
                'tenant_id' => $event->tenantId,
                'reference' => 'asset_depreciation',
                'notes'     => 'Auto-created from asset depreciation ' . $event->assetId,
                'lines'     => [
                    // Debit: Depreciation Expense
                    [
                        'account_code' => 'DEPRECIATION-EXPENSE',
                        'description'  => $description,
                        'debit'        => $event->depreciationAmount,
                        'credit'       => '0',
                    ],
                    // Credit: Accumulated Depreciation
                    [
                        'account_code' => 'ACCUMULATED-DEPRECIATION',
                        'description'  => $description,
                        'debit'        => '0',
                        'credit'       => $event->depreciationAmount,
                    ],
                ],
            ]);
        } catch (\Throwable) {
            // Graceful degradation: a journal entry creation failure must never
            // prevent the asset depreciation from being recorded.
        }
    }
}
