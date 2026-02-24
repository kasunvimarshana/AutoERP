<?php

namespace Modules\AssetManagement\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\AssetManagement\Domain\Contracts\AssetRepositoryInterface;
use Modules\AssetManagement\Domain\Events\AssetDisposed;

class DisposeAssetUseCase
{
    public function __construct(
        private AssetRepositoryInterface $assetRepo,
    ) {}

    public function execute(string $assetId, array $data = []): object
    {
        return DB::transaction(function () use ($assetId, $data) {
            $asset = $this->assetRepo->findById($assetId);

            if (! $asset) {
                throw new DomainException('Asset not found.');
            }

            if ($asset->status === 'disposed') {
                throw new DomainException('Asset has already been disposed.');
            }

            $disposalValue = (string) ($data['disposal_value'] ?? '0');

            $updated = $this->assetRepo->update($assetId, [
                'status'         => 'disposed',
                'book_value'     => $disposalValue,
                'disposal_value' => $disposalValue,
                'disposed_at'    => now(),
                'disposal_notes' => $data['disposal_notes'] ?? null,
            ]);

            Event::dispatch(new AssetDisposed($assetId, $asset->tenant_id));

            return $updated;
        });
    }
}
