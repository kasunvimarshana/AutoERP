<?php

declare(strict_types=1);

namespace Modules\JobCard\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\JobCard\Models\InspectionItem;

/**
 * InspectionItem Repository
 *
 * Handles data access for InspectionItem model
 */
class InspectionItemRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new InspectionItem;
    }

    /**
     * Get inspection items for job card
     */
    public function getForJobCard(int $jobCardId): Collection
    {
        return $this->model->newQuery()->where('job_card_id', $jobCardId)->get();
    }

    /**
     * Get inspection items by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->newQuery()->where('item_type', $type)->get();
    }

    /**
     * Get inspection items by condition
     */
    public function getByCondition(string $condition): Collection
    {
        return $this->model->newQuery()->where('condition', $condition)->get();
    }

    /**
     * Get items needing attention
     */
    public function getNeedingAttention(): Collection
    {
        return $this->model->newQuery()
            ->whereIn('condition', ['poor', 'needs_replacement'])
            ->get();
    }
}
