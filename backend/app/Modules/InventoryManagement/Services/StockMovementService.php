<?php

namespace App\Modules\InventoryManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InventoryManagement\Events\StockMovementRecorded;
use App\Modules\InventoryManagement\Repositories\StockMovementRepository;
use Illuminate\Database\Eloquent\Model;

class StockMovementService extends BaseService
{
    public function __construct(StockMovementRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After stock movement creation hook
     */
    protected function afterCreate(Model $movement, array $data): void
    {
        event(new StockMovementRecorded($movement));
    }

    /**
     * Record stock movement
     */
    public function recordMovement(array $data): Model
    {
        $data['movement_date'] = $data['movement_date'] ?? now();
        return $this->create($data);
    }

    /**
     * Get movements by item
     */
    public function getByItem(int $itemId)
    {
        return $this->repository->getByItem($itemId);
    }

    /**
     * Get movements by type
     */
    public function getByType(string $type)
    {
        return $this->repository->getByType($type);
    }

    /**
     * Get movements by date range
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    /**
     * Get recent movements
     */
    public function getRecent(int $limit = 50)
    {
        return $this->repository->getRecent($limit);
    }

    /**
     * Calculate total movement value by type
     */
    public function getTotalValueByType(string $type, \DateTime $startDate, \DateTime $endDate): float
    {
        return $this->repository->getTotalValueByType($type, $startDate, $endDate);
    }
}
