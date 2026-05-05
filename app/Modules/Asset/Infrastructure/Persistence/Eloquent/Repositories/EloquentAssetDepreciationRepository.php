<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Asset\Domain\Entities\AssetDepreciation;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDepreciationRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetDepreciationModel;

class EloquentAssetDepreciationRepository implements AssetDepreciationRepositoryInterface
{
    public function create(AssetDepreciation $depreciation): void
    {
        AssetDepreciationModel::create([
            'id' => $depreciation->getId(),
            'tenant_id' => $depreciation->getTenantId(),
            'asset_id' => $depreciation->getAssetId(),
            'year' => $depreciation->getYear(),
            'month' => $depreciation->getMonth(),
            'original_cost' => $depreciation->getAcquisitionCost(),
            'depreciation_amount' => $depreciation->getDepreciationAmount(),
            'accumulated_depreciation' => $depreciation->getAccumulatedDepreciation(),
            'book_value' => $depreciation->getBookValue(),
            'posting_status' => 'pending',
        ]);
    }

    public function findById(string $id): ?AssetDepreciation
    {
        $model = AssetDepreciationModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function getByAsset(string $assetId, int $page = 1, int $limit = 50): array
    {
        $query = AssetDepreciationModel::byAsset($assetId)->latest();
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
        ];
    }

    public function getPending(string $tenantId): array
    {
        $data = AssetDepreciationModel::where('tenant_id', $tenantId)
            ->pending()
            ->get()
            ->map(fn($m) => $this->toDomain($m))
            ->toArray();

        return $data;
    }

    public function getByYear(string $tenantId, int $year): array
    {
        $data = AssetDepreciationModel::where('tenant_id', $tenantId)
            ->byYear($year)
            ->get()
            ->map(fn($m) => $this->toDomain($m))
            ->toArray();

        return $data;
    }

    public function getLatestByAsset(string $assetId): ?AssetDepreciation
    {
        $model = AssetDepreciationModel::byAsset($assetId)->latest()->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function update(AssetDepreciation $depreciation): void
    {
        AssetDepreciationModel::where('id', $depreciation->getId())->update([
            'posting_status' => $depreciation->getPostingStatus(),
            'journal_entry_id' => $depreciation->getJournalEntryId(),
        ]);
    }

    public function delete(string $id): void
    {
        AssetDepreciationModel::where('id', $id)->delete();
    }

    private function toDomain(AssetDepreciationModel $model): AssetDepreciation
    {
        return new AssetDepreciation(
            $model->id,
            $model->tenant_id,
            $model->asset_id,
            $model->year,
            $model->month,
            $model->original_cost,
            $model->salvage_value,
            $model->depreciation_method,
            $model->useful_life_years,
            $model->depreciation_amount,
            $model->accumulated_depreciation,
            $model->book_value,
            $model->journal_entry_id,
            $model->posting_status,
        );
    }
}
