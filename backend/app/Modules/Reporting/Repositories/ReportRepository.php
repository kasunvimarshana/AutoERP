<?php

namespace App\Modules\Reporting\Repositories;

use App\Modules\Reporting\Models\Report;
use App\Repositories\BaseRepository;

/**
 * Report Repository
 *
 * Handles data access for reports.
 */
class ReportRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Report::class;
    }

    /**
     * Get reports by module
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByModule(string $module)
    {
        return $this->model->where('module', $module)->get();
    }

    /**
     * Get public reports
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPublicReports()
    {
        return $this->model->where('is_public', true)->get();
    }

    /**
     * Get reports created by user
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCreator(int $userId)
    {
        return $this->model->where('created_by_id', $userId)->get();
    }

    /**
     * Search reports by name
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $search)
    {
        return $this->model
            ->where('name', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%")
            ->get();
    }

    /**
     * Get reports with executions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWithExecutions()
    {
        return $this->model->with('executions')->get();
    }
}
