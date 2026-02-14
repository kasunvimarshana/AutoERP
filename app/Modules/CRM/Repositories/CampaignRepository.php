<?php

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Campaign;
use Illuminate\Database\Eloquent\Collection;

class CampaignRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Campaign::class;
    }

    /**
     * Get active campaigns
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get campaigns by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('campaign_type', $type)->get();
    }

    /**
     * Get campaigns by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get campaigns within date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('start_date', [$startDate, $endDate])->get();
    }
}
