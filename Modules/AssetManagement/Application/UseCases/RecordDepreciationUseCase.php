<?php

namespace Modules\AssetManagement\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\AssetManagement\Domain\Contracts\AssetRepositoryInterface;
use Modules\AssetManagement\Domain\Events\AssetDepreciated;


class RecordDepreciationUseCase
{
    public function __construct(
        private AssetRepositoryInterface $assetRepo,
    ) {}

    public function execute(string $assetId, array $data): object
    {
        return DB::transaction(function () use ($assetId, $data) {
            $asset = $this->assetRepo->findById($assetId);

            if (! $asset) {
                throw new DomainException('Asset not found.');
            }

            if ($asset->status === 'disposed') {
                throw new DomainException('Cannot record depreciation for a disposed asset.');
            }

            $amount = bcadd((string) ($data['depreciation_amount'] ?? '0'), '0', 8);

            if (bccomp($amount, '0', 8) <= 0) {
                throw new DomainException('Depreciation amount must be greater than zero.');
            }

            $currentBookValue = bcadd((string) ($asset->book_value ?? '0'), '0', 8);

            if (bccomp($amount, $currentBookValue, 8) > 0) {
                throw new DomainException('Depreciation amount cannot exceed the current book value.');
            }

            $newBookValue = bcsub($currentBookValue, $amount, 8);

            $updated = $this->assetRepo->update($assetId, [
                'book_value' => $newBookValue,
            ]);

            Event::dispatch(new AssetDepreciated(
                assetId:            $assetId,
                tenantId:           $asset->tenant_id,
                depreciationAmount: $amount,
                bookValueAfter:     $newBookValue,
                assetName:          (string) ($asset->name ?? ''),
                periodLabel:        (string) ($data['period_label'] ?? ''),
            ));

            return $updated;
        });
    }
}
