<?php
declare(strict_types=1);
namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Asset\Domain\Entities\AssetDepreciation;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDepreciationRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetDepreciationModel;

class EloquentAssetDepreciationRepository implements AssetDepreciationRepositoryInterface
{
    public function __construct(private readonly AssetDepreciationModel $model) {}

    public function findById(int $id): ?AssetDepreciation
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByAsset(int $assetId): array
    {
        return $this->model->newQuery()->where('asset_id', $assetId)
            ->orderBy('period_year')->orderBy('period_month')
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function findByPeriod(int $tenantId, int $year, int $month): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): AssetDepreciation
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    private function toEntity(AssetDepreciationModel $m): AssetDepreciation
    {
        return new AssetDepreciation(
            $m->id, $m->tenant_id, $m->asset_id, $m->type,
            (int) $m->period_year, (int) $m->period_month,
            (float) $m->amount, (float) $m->book_value_before, (float) $m->book_value_after,
            $m->journal_entry_id, $m->depreciated_at, $m->created_at,
        );
    }
}
