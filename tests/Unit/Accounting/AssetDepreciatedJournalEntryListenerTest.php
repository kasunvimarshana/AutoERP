<?php

namespace Tests\Unit\Accounting;

use DomainException;
use Mockery;
use Modules\Accounting\Application\Listeners\HandleAssetDepreciatedListener;
use Modules\Accounting\Application\UseCases\CreateJournalEntryUseCase;
use Modules\AssetManagement\Domain\Events\AssetDepreciated;
use PHPUnit\Framework\TestCase;


class AssetDepreciatedJournalEntryListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEvent(
        string $assetId            = 'asset-1',
        string $tenantId           = 'tenant-1',
        string $depreciationAmount = '200.00000000',
        string $bookValueAfter     = '800.00000000',
        string $assetName          = 'Server Rack',
        string $periodLabel        = 'Q1 2026',
    ): AssetDepreciated {
        return new AssetDepreciated(
            assetId:            $assetId,
            tenantId:           $tenantId,
            depreciationAmount: $depreciationAmount,
            bookValueAfter:     $bookValueAfter,
            assetName:          $assetName,
            periodLabel:        $periodLabel,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldNotReceive('execute');

        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent(tenantId: ''));

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when depreciationAmount is zero
    // -------------------------------------------------------------------------

    public function test_skips_when_depreciation_amount_is_zero(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldNotReceive('execute');

        (new HandleAssetDepreciatedListener($useCase))->handle(
            $this->makeEvent(depreciationAmount: '0')
        );

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when depreciationAmount is negative
    // -------------------------------------------------------------------------

    public function test_skips_when_depreciation_amount_is_negative(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldNotReceive('execute');

        (new HandleAssetDepreciatedListener($useCase))->handle(
            $this->makeEvent(depreciationAmount: '-100')
        );

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Journal entry tenant and reference
    // -------------------------------------------------------------------------

    public function test_creates_journal_entry_with_correct_tenant_id(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) => $data['tenant_id'] === 'tenant-1')
            ->andReturn((object) []);

        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_journal_entry_reference_is_asset_depreciation(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) => $data['reference'] === 'asset_depreciation')
            ->andReturn((object) []);

        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Journal entry lines: debit / credit amounts
    // -------------------------------------------------------------------------

    public function test_depreciation_expense_line_debits_correct_amount(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) =>
                collect($data['lines'])->contains(fn ($l) =>
                    $l['account_code'] === 'DEPRECIATION-EXPENSE' &&
                    $l['debit']        === '200.00000000' &&
                    $l['credit']       === '0'
                )
            )
            ->andReturn((object) []);

        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_accumulated_depreciation_line_credits_correct_amount(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) =>
                collect($data['lines'])->contains(fn ($l) =>
                    $l['account_code'] === 'ACCUMULATED-DEPRECIATION' &&
                    $l['debit']        === '0' &&
                    $l['credit']       === '200.00000000'
                )
            )
            ->andReturn((object) []);

        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Notes reference the asset id
    // -------------------------------------------------------------------------

    public function test_notes_reference_asset_id(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) => str_contains($data['notes'], 'asset-1'))
            ->andReturn((object) []);

        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Asset name and period label appear in line descriptions
    // -------------------------------------------------------------------------

    public function test_asset_name_appears_in_line_description(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) =>
                str_contains($data['lines'][0]['description'], 'Server Rack')
            )
            ->andReturn((object) []);

        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_period_label_appears_in_line_description(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) =>
                str_contains($data['lines'][0]['description'], 'Q1 2026')
            )
            ->andReturn((object) []);

        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_line_description_works_without_asset_name_and_period_label(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) =>
                str_contains($data['lines'][0]['description'], 'Depreciation')
            )
            ->andReturn((object) []);

        (new HandleAssetDepreciatedListener($useCase))->handle(
            $this->makeEvent(assetName: '', periodLabel: '')
        );

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_on_domain_exception(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new DomainException('Some accounting error'));

        // Must not throw
        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_graceful_degradation_on_runtime_exception(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('DB connection lost'));

        // Must not throw
        (new HandleAssetDepreciatedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }
}
