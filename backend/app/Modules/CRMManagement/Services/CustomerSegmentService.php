<?php

namespace App\Modules\CRMManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\CRMManagement\Events\SegmentCreated;
use App\Modules\CRMManagement\Repositories\CustomerSegmentRepository;
use Illuminate\Database\Eloquent\Model;

class CustomerSegmentService extends BaseService
{
    public function __construct(CustomerSegmentRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After segment creation hook
     */
    protected function afterCreate(Model $segment, array $data): void
    {
        event(new SegmentCreated($segment));
    }

    /**
     * Get active segments
     */
    public function getActive()
    {
        return $this->repository->getActive();
    }

    /**
     * Get customers in segment
     */
    public function getCustomers(int $segmentId)
    {
        return $this->repository->getCustomers($segmentId);
    }

    /**
     * Add customer to segment
     */
    public function addCustomer(int $segmentId, int $customerId): void
    {
        $segment = $this->repository->findOrFail($segmentId);
        $customerIds = $segment->customer_ids ?? [];
        
        if (!in_array($customerId, $customerIds)) {
            $customerIds[] = $customerId;
            $this->update($segmentId, ['customer_ids' => $customerIds]);
        }
    }

    /**
     * Remove customer from segment
     */
    public function removeCustomer(int $segmentId, int $customerId): void
    {
        $segment = $this->repository->findOrFail($segmentId);
        $customerIds = $segment->customer_ids ?? [];
        
        $customerIds = array_filter($customerIds, function ($id) use ($customerId) {
            return $id !== $customerId;
        });
        
        $this->update($segmentId, ['customer_ids' => array_values($customerIds)]);
    }

    /**
     * Get segment size
     */
    public function getSize(int $segmentId): int
    {
        $segment = $this->repository->findOrFail($segmentId);
        return count($segment->customer_ids ?? []);
    }

    /**
     * Update segment criteria
     */
    public function updateCriteria(int $segmentId, array $criteria): Model
    {
        return $this->update($segmentId, ['criteria' => $criteria]);
    }

    /**
     * Activate segment
     */
    public function activate(int $segmentId): Model
    {
        return $this->update($segmentId, ['is_active' => true]);
    }

    /**
     * Deactivate segment
     */
    public function deactivate(int $segmentId): Model
    {
        return $this->update($segmentId, ['is_active' => false]);
    }

    /**
     * Refresh segment (recalculate customers based on criteria)
     */
    public function refresh(int $segmentId): Model
    {
        $segment = $this->repository->findOrFail($segmentId);
        
        // Apply criteria and get matching customer IDs
        $customerIds = $this->repository->applySegmentCriteria($segment->criteria);
        
        return $this->update($segmentId, [
            'customer_ids' => $customerIds,
            'last_refreshed_at' => now()
        ]);
    }
}
