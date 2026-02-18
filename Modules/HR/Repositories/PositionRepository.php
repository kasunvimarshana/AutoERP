<?php

declare(strict_types=1);

namespace Modules\HR\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\HR\Entities\Position;
use Modules\HR\Repositories\Contracts\PositionRepositoryInterface;

/**
 * Position Repository Implementation
 *
 * Handles all position data access operations.
 */
class PositionRepository extends BaseRepository implements PositionRepositoryInterface
{
    /**
     * PositionRepository constructor.
     */
    public function __construct(Position $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?Position
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByGrade(string $grade): Collection
    {
        return $this->model->where('grade', $grade)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActivePositions(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('title', 'LIKE', "%{$query}%")
            ->orWhere('code', 'LIKE', "%{$query}%")
            ->get();
    }
}
