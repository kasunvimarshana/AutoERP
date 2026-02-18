<?php

declare(strict_types=1);

namespace Modules\HR\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\HR\Entities\LeaveType;
use Modules\HR\Repositories\Contracts\LeaveTypeRepositoryInterface;

/**
 * LeaveType Repository Implementation
 *
 * Handles all leave type data access operations.
 */
class LeaveTypeRepository extends BaseRepository implements LeaveTypeRepositoryInterface
{
    /**
     * LeaveTypeRepository constructor.
     */
    public function __construct(LeaveType $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?LeaveType
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveLeaveTypes(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getPaidLeaveTypes(): Collection
    {
        return $this->model->where('is_paid', true)->where('status', 'active')->get();
    }
}
