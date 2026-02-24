<?php

namespace Tests\Unit\AssetManagement;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\AssetManagement\Application\UseCases\DisposeAssetUseCase;
use Modules\AssetManagement\Application\UseCases\RegisterAssetUseCase;
use Modules\AssetManagement\Domain\Contracts\AssetRepositoryInterface;
use Modules\AssetManagement\Domain\Events\AssetAcquired;
use Modules\AssetManagement\Domain\Events\AssetDisposed;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Asset Management use cases.
 *
 * Covers asset registration with BCMath depreciation calculation,
 * disposal guards, and domain event dispatch.
 */
class AssetManagementUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeAsset(string $status = 'active'): object
    {
        return (object) [
            'id'                   => 'asset-uuid-1',
            'tenant_id'            => 'tenant-uuid-1',
            'name'                 => 'Office Laptop',
            'purchase_cost'        => '1200.00000000',
            'salvage_value'        => '200.00000000',
            'useful_life_years'    => 5,
            'depreciation_method'  => 'straight_line',
            'annual_depreciation'  => '200.00000000',
            'book_value'           => '1200.00000000',
            'status'               => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // RegisterAssetUseCase
    // -------------------------------------------------------------------------

    public function test_registers_asset_with_straight_line_depreciation(): void
    {
        // (1200 - 200) / 5 = 200.00000000
        $asset = $this->makeAsset('active');

        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) =>
                $data['status'] === 'active' &&
                $data['annual_depreciation'] === '200.00000000' &&
                $data['book_value'] === '1200.00000000'
            )
            ->andReturn($asset);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof AssetAcquired);

        $useCase = new RegisterAssetUseCase($assetRepo);
        $result  = $useCase->execute([
            'tenant_id'          => 'tenant-uuid-1',
            'name'               => 'Office Laptop',
            'purchase_cost'      => '1200',
            'salvage_value'      => '200',
            'useful_life_years'  => 5,
            'depreciation_method' => 'straight_line',
        ]);

        $this->assertSame('active', $result->status);
        $this->assertSame('200.00000000', $result->annual_depreciation);
    }

    public function test_registers_asset_with_zero_depreciation_when_no_useful_life(): void
    {
        $asset = (object) array_merge((array) $this->makeAsset(), [
            'annual_depreciation' => '0.00000000',
            'useful_life_years'   => 0,
        ]);

        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['annual_depreciation'] === '0.00000000')
            ->andReturn($asset);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new RegisterAssetUseCase($assetRepo);
        $result  = $useCase->execute([
            'tenant_id'         => 'tenant-uuid-1',
            'name'              => 'Land',
            'purchase_cost'     => '50000',
            'salvage_value'     => '50000',
            'useful_life_years' => 0,
        ]);

        $this->assertSame('0.00000000', $result->annual_depreciation);
    }

    // -------------------------------------------------------------------------
    // DisposeAssetUseCase
    // -------------------------------------------------------------------------

    public function test_dispose_throws_when_asset_not_found(): void
    {
        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new DisposeAssetUseCase($assetRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_dispose_throws_when_already_disposed(): void
    {
        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($this->makeAsset('disposed'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new DisposeAssetUseCase($assetRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already been disposed/i');

        $useCase->execute('asset-uuid-1');
    }

    public function test_dispose_transitions_to_disposed_and_dispatches_event(): void
    {
        $asset    = $this->makeAsset('active');
        $disposed = (object) array_merge((array) $asset, ['status' => 'disposed']);

        $assetRepo = Mockery::mock(AssetRepositoryInterface::class);
        $assetRepo->shouldReceive('findById')->andReturn($asset);
        $assetRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'disposed')
            ->once()
            ->andReturn($disposed);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof AssetDisposed);

        $useCase = new DisposeAssetUseCase($assetRepo);
        $result  = $useCase->execute('asset-uuid-1', ['disposal_value' => '150', 'disposal_notes' => 'Sold']);

        $this->assertSame('disposed', $result->status);
    }
}
