<?php

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Opportunity;
use Illuminate\Database\Eloquent\Collection;

class OpportunityRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Opportunity::class;
    }

    /**
     * Get opportunities by stage
     */
    public function getByStage(string $stage): Collection
    {
        return $this->model->where('stage', $stage)->get();
    }

    /**
     * Get opportunities by assigned user
     */
    public function getByAssignedUser(int $userId): Collection
    {
        return $this->model->where('assigned_to', $userId)->get();
    }

    /**
     * Get won opportunities
     */
    public function getWon(): Collection
    {
        return $this->model->where('stage', 'won')->get();
    }

    /**
     * Get opportunities by value range
     */
    public function getByValueRange(float $min, float $max): Collection
    {
        return $this->model->whereBetween('value', [$min, $max])->get();
    }
}
