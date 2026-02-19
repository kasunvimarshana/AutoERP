<?php

declare(strict_types=1);

namespace Modules\JobCard\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\JobCard\Models\JobPart;

/**
 * JobPart Repository
 *
 * Handles data access for JobPart model
 */
class JobPartRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new JobPart;
    }

    /**
     * Get parts for job card
     */
    public function getForJobCard(int $jobCardId): Collection
    {
        return $this->model->newQuery()->where('job_card_id', $jobCardId)->get();
    }

    /**
     * Get parts by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()->where('status', $status)->get();
    }

    /**
     * Calculate total for job card
     */
    public function getTotalForJobCard(int $jobCardId): float
    {
        return (float) $this->model->newQuery()
            ->where('job_card_id', $jobCardId)
            ->sum('total_price');
    }
}
