<?php
declare(strict_types=1);
namespace Tests\Unit;

use Modules\Asset\Application\Services\ManageFixedAssetService;
use Modules\Asset\Application\Services\RecordDepreciationService;
use Modules\Asset\Domain\Entities\AssetDepreciation;
use Modules\Asset\Domain\Entities\FixedAsset;
use Modules\Asset\Domain\Exceptions\AssetDepreciationException;
use Modules\Asset\Domain\Exceptions\FixedAssetNotFoundException;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDepreciationRepositoryInterface;
use Modules\Asset\Domain\RepositoryInterfaces\FixedAssetRepositoryInterface;
use PHPUnit\Framework\TestCase;

class AssetModuleTest extends TestCase
{
    // ── helpers ────────────────────────────────────────────────────────────
    private function makeAsset(
        ?int $id = 1,
        string $status = FixedAsset::STATUS_ACTIVE,
        string $method = FixedAsset::DEPRECIATION_STRAIGHT_LINE,
        float $cost = 12000.0,
        float $residual = 0.0,
        int $usefulMonths = 120,
    ): FixedAsset {
        return new FixedAsset(
            $id, 1, 'ASSET-001', 'Office Server', null, 'Equipment',
            null, null,
            $cost, $residual, $usefulMonths, $method,
            null, null, $status,
            new \DateTimeImmutable('2024-01-01'), null, null, null,
        );
    }

    private function makeDepreciation(int $assetId = 1, int $year = 2024, int $month = 1, float $amount = 100.0): AssetDepreciation
    {
        return new AssetDepreciation(
            1, 1, $assetId, AssetDepreciation::TYPE_SCHEDULED,
            $year, $month, $amount, 12000.0, 11900.0,
            null, new \DateTimeImmutable(), null,
        );
    }

    // ── FixedAsset entity ────────────────────────────────────────────────
    public function testFixedAssetCreation(): void
    {
        $asset = $this->makeAsset();
        $this->assertSame('ASSET-001', $asset->getCode());
        $this->assertSame('Office Server', $asset->getName());
        $this->assertTrue($asset->isActive());
        $this->assertSame(FixedAsset::STATUS_ACTIVE, $asset->getStatus());
    }

    public function testFixedAssetMonthlyDepreciationStraightLine(): void
    {
        $asset = $this->makeAsset(cost: 12000.0, residual: 0.0, usefulMonths: 120);
        $this->assertEqualsWithDelta(100.0, $asset->monthlyDepreciation(), 0.001);
    }

    public function testFixedAssetMonthlyDepreciationWithResidual(): void
    {
        $asset = $this->makeAsset(cost: 12000.0, residual: 2000.0, usefulMonths: 100);
        $this->assertEqualsWithDelta(100.0, $asset->monthlyDepreciation(), 0.001);
    }

    public function testFixedAssetMonthlyDepreciationNone(): void
    {
        $asset = $this->makeAsset(method: FixedAsset::DEPRECIATION_NONE);
        $this->assertSame(0.0, $asset->monthlyDepreciation());
    }

    public function testFixedAssetDispose(): void
    {
        $asset = $this->makeAsset();
        $date  = new \DateTimeImmutable('2026-01-01');
        $asset->dispose($date);
        $this->assertSame(FixedAsset::STATUS_DISPOSED, $asset->getStatus());
        $this->assertSame($date, $asset->getDisposalDate());
    }

    public function testFixedAssetDisposeTwiceThrows(): void
    {
        $this->expectException(\DomainException::class);
        $asset = $this->makeAsset(status: FixedAsset::STATUS_DISPOSED);
        $asset->dispose(new \DateTimeImmutable());
    }

    public function testFixedAssetSell(): void
    {
        $asset = $this->makeAsset();
        $asset->sell(new \DateTimeImmutable('2026-06-01'));
        $this->assertSame(FixedAsset::STATUS_SOLD, $asset->getStatus());
        $this->assertFalse($asset->isActive());
    }

    public function testFixedAssetSellAlreadySoldThrows(): void
    {
        $this->expectException(\DomainException::class);
        $asset = $this->makeAsset(status: FixedAsset::STATUS_SOLD);
        $asset->sell(new \DateTimeImmutable());
    }

    // ── AssetDepreciation entity ─────────────────────────────────────────
    public function testAssetDepreciationCreation(): void
    {
        $dep = $this->makeDepreciation(year: 2024, month: 3, amount: 100.0);
        $this->assertSame(1, $dep->getAssetId());
        $this->assertSame(2024, $dep->getPeriodYear());
        $this->assertSame(3, $dep->getPeriodMonth());
        $this->assertEqualsWithDelta(100.0, $dep->getAmount(), 0.001);
        $this->assertSame(AssetDepreciation::TYPE_SCHEDULED, $dep->getType());
    }

    // ── ManageFixedAssetService ───────────────────────────────────────────
    public function testManageServiceFindById(): void
    {
        $asset = $this->makeAsset();
        $repo  = $this->createMock(FixedAssetRepositoryInterface::class);
        $repo->method('findById')->willReturn($asset);
        $result = (new ManageFixedAssetService($repo))->findById(1);
        $this->assertSame('ASSET-001', $result->getCode());
    }

    public function testManageServiceFindNotFoundThrows(): void
    {
        $repo = $this->createMock(FixedAssetRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(FixedAssetNotFoundException::class);
        (new ManageFixedAssetService($repo))->findById(999);
    }

    public function testManageServiceCreate(): void
    {
        $asset = $this->makeAsset();
        $repo  = $this->createMock(FixedAssetRepositoryInterface::class);
        $repo->method('create')->willReturn($asset);
        $result = (new ManageFixedAssetService($repo))->create(['code' => 'ASSET-001']);
        $this->assertSame(1, $result->getId());
    }

    public function testManageServiceDispose(): void
    {
        $asset    = $this->makeAsset();
        $disposed = $this->makeAsset(status: FixedAsset::STATUS_DISPOSED);
        $repo     = $this->createMock(FixedAssetRepositoryInterface::class);
        $repo->method('findById')->willReturn($asset);
        $repo->method('update')->willReturn($disposed);
        $result = (new ManageFixedAssetService($repo))->dispose(1, new \DateTimeImmutable());
        $this->assertSame(FixedAsset::STATUS_DISPOSED, $result->getStatus());
    }

    // ── RecordDepreciationService ─────────────────────────────────────────
    public function testRecordDepreciationSuccess(): void
    {
        $asset    = $this->makeAsset();
        $assetRepo = $this->createMock(FixedAssetRepositoryInterface::class);
        $assetRepo->method('findById')->willReturn($asset);

        $depRecord = $this->makeDepreciation(amount: 100.0);
        $depRepo   = $this->createMock(AssetDepreciationRepositoryInterface::class);
        $depRepo->method('findByAsset')->willReturn([]);
        $depRepo->method('create')->willReturn($depRecord);

        $svc    = new RecordDepreciationService($assetRepo, $depRepo);
        $result = $svc->record(1, 2024, 1);
        $this->assertEqualsWithDelta(100.0, $result->getAmount(), 0.001);
    }

    public function testRecordDepreciationDuplicatePeriodThrows(): void
    {
        $asset    = $this->makeAsset();
        $assetRepo = $this->createMock(FixedAssetRepositoryInterface::class);
        $assetRepo->method('findById')->willReturn($asset);

        $depRepo = $this->createMock(AssetDepreciationRepositoryInterface::class);
        $depRepo->method('findByAsset')->willReturn([
            $this->makeDepreciation(year: 2024, month: 1),
        ]);

        $this->expectException(AssetDepreciationException::class);
        (new RecordDepreciationService($assetRepo, $depRepo))->record(1, 2024, 1);
    }

    public function testRecordDepreciationInactiveAssetThrows(): void
    {
        $asset     = $this->makeAsset(status: FixedAsset::STATUS_DISPOSED);
        $assetRepo = $this->createMock(FixedAssetRepositoryInterface::class);
        $assetRepo->method('findById')->willReturn($asset);

        $depRepo = $this->createMock(AssetDepreciationRepositoryInterface::class);
        $depRepo->method('findByAsset')->willReturn([]);

        $this->expectException(AssetDepreciationException::class);
        (new RecordDepreciationService($assetRepo, $depRepo))->record(1, 2024, 1);
    }

    public function testRecordDepreciationAssetNotFoundThrows(): void
    {
        $assetRepo = $this->createMock(FixedAssetRepositoryInterface::class);
        $assetRepo->method('findById')->willReturn(null);
        $depRepo   = $this->createMock(AssetDepreciationRepositoryInterface::class);

        $this->expectException(FixedAssetNotFoundException::class);
        (new RecordDepreciationService($assetRepo, $depRepo))->record(99, 2024, 1);
    }

    public function testRecordDepreciationNeverBelowResidual(): void
    {
        // 10-month asset, already 9 months done, 1 month to go — residual = 500
        $asset = $this->makeAsset(cost: 1500.0, residual: 500.0, usefulMonths: 10);
        $assetRepo = $this->createMock(FixedAssetRepositoryInterface::class);
        $assetRepo->method('findById')->willReturn($asset);

        // 9 months * 100/month = 900 already depreciated
        $prior = [];
        for ($m = 1; $m <= 9; $m++) {
            $prior[] = $this->makeDepreciation(year: 2024, month: $m, amount: 100.0);
        }

        $captured = null;
        $depRepo = $this->createMock(AssetDepreciationRepositoryInterface::class);
        $depRepo->method('findByAsset')->willReturn($prior);
        $depRepo->method('create')->willReturnCallback(function (array $data) use (&$captured) {
            $captured = $data['amount'];
            return $this->makeDepreciation(year: 2024, month: 10, amount: $data['amount']);
        });

        (new RecordDepreciationService($assetRepo, $depRepo))->record(1, 2024, 10);
        // book value before = 1500 - 900 = 600, residual=500, so charge = min(100, 100) = 100
        $this->assertEqualsWithDelta(100.0, $captured, 0.001);
    }

    public function testDeclineBalanceDepreciation(): void
    {
        // Cost 12000, 12-month life, declining balance — first month: 12000 * (2/12) = 2000
        $asset = $this->makeAsset(method: FixedAsset::DEPRECIATION_DECLINING_BALANCE, cost: 12000.0, residual: 0.0, usefulMonths: 12);
        $assetRepo = $this->createMock(FixedAssetRepositoryInterface::class);
        $assetRepo->method('findById')->willReturn($asset);

        $captured  = null;
        $depRepo   = $this->createMock(AssetDepreciationRepositoryInterface::class);
        $depRepo->method('findByAsset')->willReturn([]);
        $depRepo->method('create')->willReturnCallback(function (array $data) use (&$captured) {
            $captured = $data['amount'];
            return $this->makeDepreciation(amount: $data['amount']);
        });

        (new RecordDepreciationService($assetRepo, $depRepo))->record(1, 2024, 1);
        $this->assertEqualsWithDelta(2000.0, $captured, 0.001);
    }

    // ── Exception messages ────────────────────────────────────────────────
    public function testFixedAssetNotFoundExceptionMessage(): void
    {
        $this->assertStringContainsString('15', (new FixedAssetNotFoundException(15))->getMessage());
    }
}
