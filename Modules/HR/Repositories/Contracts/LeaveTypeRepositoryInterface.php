<?php

declare(strict_types=1);

namespace Modules\HR\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\HR\Entities\LeaveType;

/**
 * LeaveType Repository Interface
 *
 * Defines the contract for leave type data access operations.
 */
interface LeaveTypeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find leave type by code.
     */
    public function findByCode(string $code): ?LeaveType;

    /**
     * Get active leave types.
     */
    public function getActiveLeaveTypes(): Collection;

    /**
     * Get paid leave types.
     */
    public function getPaidLeaveTypes(): Collection;
}
