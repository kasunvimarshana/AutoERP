<?php
declare(strict_types=1);
namespace Modules\Asset\Application\Services;

use Modules\Asset\Domain\Entities\AssetDepreciation;
use Modules\Asset\Domain\Entities\FixedAsset;
use Modules\Asset\Domain\Exceptions\AssetDepreciationException;
use Modules\Asset\Domain\Exceptions\FixedAssetNotFoundException;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDepreciationRepositoryInterface;
use Modules\Asset\Domain\RepositoryInterfaces\FixedAssetRepositoryInterface;

/**
 * Records monthly depreciation for an asset for a given period.
 * Prevents duplicate entries for the same asset/period combination.
 * Calculates book value based on prior depreciations.
 */
class RecordDepreciationService
{
    public function __construct(
        private readonly FixedAssetRepositoryInterface $assetRepository,
        private readonly AssetDepreciationRepositoryInterface $depreciationRepository,
    ) {}

    public function record(
        int $assetId,
        int $year,
        int $month,
        ?int $journalEntryId = null,
    ): AssetDepreciation {
        $asset = $this->assetRepository->findById($assetId);
        if ($asset === null) throw new FixedAssetNotFoundException($assetId);
        if (!$asset->isActive()) {
            throw new AssetDepreciationException("Cannot depreciate an inactive or disposed asset.");
        }

        $prior = $this->depreciationRepository->findByAsset($assetId);

        // Check for duplicate period
        foreach ($prior as $entry) {
            if ($entry->getPeriodYear() === $year && $entry->getPeriodMonth() === $month) {
                throw new AssetDepreciationException(
                    "Depreciation for asset {$assetId} in {$year}-{$month} already recorded."
                );
            }
        }

        $totalDepreciated = array_sum(array_map(fn($e) => $e->getAmount(), $prior));
        $bookValueBefore  = $asset->getPurchaseCost() - $totalDepreciated;

        $monthlyCharge = $this->calculateMonthly($asset, $bookValueBefore);

        // Never depreciate below residual value
        $charge = min($monthlyCharge, max(0.0, $bookValueBefore - $asset->getResidualValue()));
        $bookValueAfter = $bookValueBefore - $charge;

        return $this->depreciationRepository->create([
            'tenant_id'          => $asset->getTenantId(),
            'asset_id'           => $assetId,
            'type'               => AssetDepreciation::TYPE_SCHEDULED,
            'period_year'        => $year,
            'period_month'       => $month,
            'amount'             => round($charge, 6),
            'book_value_before'  => round($bookValueBefore, 6),
            'book_value_after'   => round($bookValueAfter, 6),
            'journal_entry_id'   => $journalEntryId,
            'depreciated_at'     => new \DateTimeImmutable("{$year}-{$month}-01"),
        ]);
    }

    private function calculateMonthly(FixedAsset $asset, float $bookValue): float
    {
        return match ($asset->getDepreciationMethod()) {
            FixedAsset::DEPRECIATION_STRAIGHT_LINE    => $asset->monthlyDepreciation(),
            FixedAsset::DEPRECIATION_DECLINING_BALANCE =>
                $asset->getUsefulLifeMonths() > 0
                    ? $bookValue * (2.0 / $asset->getUsefulLifeMonths())
                    : 0.0,
            default => 0.0,
        };
    }
}
