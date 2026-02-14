<?php

namespace App\Modules\JobCards\Repositories;

use App\Core\BaseRepository;
use App\Modules\JobCards\Models\JobCardTask;

class JobCardTaskRepository extends BaseRepository
{
    public function __construct(JobCardTask $model)
    {
        parent::__construct($model);
    }

    public function getByJobCard(int $jobCardId)
    {
        return $this->model->where('job_card_id', $jobCardId)->get();
    }

    public function getBySku(int $skuId)
    {
        return $this->model->where('sku_id', $skuId)->get();
    }

    public function getByTaskType(string $taskType)
    {
        return $this->model->where('task_type', $taskType)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getCompletedByUser(int $userId)
    {
        return $this->model->where('completed_by', $userId)->get();
    }

    public function getTotalByJobCard(int $jobCardId)
    {
        return $this->model->where('job_card_id', $jobCardId)->sum('line_total');
    }
}
