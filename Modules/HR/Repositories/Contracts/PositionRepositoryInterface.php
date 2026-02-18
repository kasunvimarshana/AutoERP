<?php

declare(strict_types=1);

namespace Modules\HR\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\HR\Entities\Position;

/**
 * Position Repository Interface
 *
 * Defines the contract for position data access operations.
 */
interface PositionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find position by code.
     */
    public function findByCode(string $code): ?Position;

    /**
     * Get positions by grade.
     */
    public function getByGrade(string $grade): Collection;

    /**
     * Get active positions.
     */
    public function getActivePositions(): Collection;

    /**
     * Search positions by title.
     */
    public function search(string $query): Collection;
}
