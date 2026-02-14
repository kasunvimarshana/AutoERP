<?php

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Lead;
use Illuminate\Database\Eloquent\Collection;

class LeadRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Lead::class;
    }

    /**
     * Get leads by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get leads by assigned user
     */
    public function getByAssignedUser(int $userId): Collection
    {
        return $this->model->where('assigned_to', $userId)->get();
    }

    /**
     * Get leads by source
     */
    public function getBySource(string $source): Collection
    {
        return $this->model->where('source', $source)->get();
    }

    /**
     * Get qualified leads
     */
    public function getQualified(): Collection
    {
        return $this->model->where('is_qualified', true)->get();
    }
}
