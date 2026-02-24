<?php

namespace Tests\Unit\AssetManagement;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\AssetManagement\Application\UseCases\RecordDepreciationUseCase;
use Modules\AssetManagement\Domain\Contracts\AssetRepositoryInterface;
use Modules\AssetManagement\Domain\Events\AssetDepreciated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RecordDepreciationUseCase.
 *
 * Covers guards (not-found, disposed, non-positive amount, exceeds book value),
 * BCMath book-value reduction, domain event dispatch with enriched payload, and
 * the backwards-compatible AssetDepreciated event defaults.
 */
class RecordDepreciationUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeAsset(string $status = 'active', string $bookValue = '1000.00000000'): object
    {
        return (object) [
            'id'         => 'asset-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'name'       => 'Server Rack',
            'book_value' => $bookValue,
            'status'     => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // Guard: asset not found
    // -------------------------------------------------------------------------

    public function test_throws_when_asset_not_found(): void
    {
        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        (new RecordDepreciationUseCase($assetRepo))->execute('missing-id', ['depreciation_amount' => '100']);
    }

    // -------------------------------------------------------------------------
    // Guard: disposed asset
    // -------------------------------------------------------------------------

    public function test_throws_when_asset_is_disposed(): void
    {
        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($this->makeAsset('disposed'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/disposed/i');

        (new RecordDepreciationUseCase($assetRepo))->execute('asset-uuid-1', ['depreciation_amount' => '100']);
    }

    // -------------------------------------------------------------------------
    // Guard: non-positive depreciation amount
    // -------------------------------------------------------------------------

    public function test_throws_when_depreciation_amount_is_zero(): void
    {
        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($this->makeAsset());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/greater than zero/i');

        (new RecordDepreciationUseCase($assetRepo))->execute('asset-uuid-1', ['depreciation_amount' => '0']);
    }

    public function test_throws_when_depreciation_amount_is_negative(): void
    {
        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($this->makeAsset());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/greater than zero/i');

        (new RecordDepreciationUseCase($assetRepo))->execute('asset-uuid-1', ['depreciation_amount' => '-50']);
    }

    // -------------------------------------------------------------------------
    // Guard: depreciation exceeds book value
    // -------------------------------------------------------------------------

    public function test_throws_when_depreciation_amount_exceeds_book_value(): void
    {
        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($this->makeAsset('active', '300.00000000'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/cannot exceed.*book value/i');

        (new RecordDepreciationUseCase($assetRepo))->execute('asset-uuid-1', ['depreciation_amount' => '400']);
    }

    // -------------------------------------------------------------------------
    // Successful depreciation
    // -------------------------------------------------------------------------

    public function test_reduces_book_value_by_depreciation_amount_using_bcmath(): void
    {
        // book_value: 1000.00000000, depreciation: 200, expected new: 800.00000000
        $asset   = $this->makeAsset('active', '1000.00000000');
        $updated = (object) array_merge((array) $asset, ['book_value' => '800.00000000']);

        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($asset);
        $assetRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) =>
                $id === 'asset-uuid-1' &&
                $data['book_value'] === '800.00000000'
            )
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $result = (new RecordDepreciationUseCase($assetRepo))->execute('asset-uuid-1', [
            'depreciation_amount' => '200',
        ]);

        $this->assertSame('800.00000000', $result->book_value);
    }

    public function test_dispatches_asset_depreciated_event_with_enriched_fields(): void
    {
        $asset   = $this->makeAsset('active', '1000.00000000');
        $updated = (object) array_merge((array) $asset, ['book_value' => '800.00000000']);

        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($asset);
        $assetRepo->shouldReceive('update')->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) =>
                $event instanceof AssetDepreciated &&
                $event->assetId            === 'asset-uuid-1' &&
                $event->tenantId           === 'tenant-uuid-1' &&
                $event->depreciationAmount === '200.00000000' &&
                $event->bookValueAfter     === '800.00000000' &&
                $event->assetName          === 'Server Rack'
            );

        (new RecordDepreciationUseCase($assetRepo))->execute('asset-uuid-1', [
            'depreciation_amount' => '200',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_passes_period_label_to_event(): void
    {
        $asset   = $this->makeAsset('active', '1000.00000000');
        $updated = (object) array_merge((array) $asset, ['book_value' => '800.00000000']);

        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($asset);
        $assetRepo->shouldReceive('update')->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) =>
                $event instanceof AssetDepreciated &&
                $event->periodLabel === 'Q1 2026'
            );

        (new RecordDepreciationUseCase($assetRepo))->execute('asset-uuid-1', [
            'depreciation_amount' => '200',
            'period_label'        => 'Q1 2026',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_depreciation_equal_to_book_value_is_allowed(): void
    {
        // Full write-off: depreciation == book_value
        $asset   = $this->makeAsset('active', '500.00000000');
        $updated = (object) array_merge((array) $asset, ['book_value' => '0.00000000']);

        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($asset);
        $assetRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['book_value'] === '0.00000000')
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $result = (new RecordDepreciationUseCase($assetRepo))->execute('asset-uuid-1', [
            'depreciation_amount' => '500',
        ]);

        $this->assertSame('0.00000000', $result->book_value);
    }

    // -------------------------------------------------------------------------
    // AssetDepreciated event defaults (backwards compatibility)
    // -------------------------------------------------------------------------

    public function test_asset_depreciated_event_defaults_optional_fields(): void
    {
        $event = new AssetDepreciated(
            assetId:  'asset-1',
            tenantId: 'tenant-1',
        );

        $this->assertSame('0', $event->depreciationAmount);
        $this->assertSame('0', $event->bookValueAfter);
        $this->assertSame('', $event->assetName);
        $this->assertSame('', $event->periodLabel);
    }

    public function test_asset_depreciated_event_carries_all_enriched_fields(): void
    {
        $event = new AssetDepreciated(
            assetId:            'asset-1',
            tenantId:           'tenant-1',
            depreciationAmount: '200.00000000',
            bookValueAfter:     '800.00000000',
            assetName:          'Server Rack',
            periodLabel:        'Q1 2026',
        );

        $this->assertSame('200.00000000', $event->depreciationAmount);
        $this->assertSame('800.00000000', $event->bookValueAfter);
        $this->assertSame('Server Rack', $event->assetName);
        $this->assertSame('Q1 2026', $event->periodLabel);
    }
}
