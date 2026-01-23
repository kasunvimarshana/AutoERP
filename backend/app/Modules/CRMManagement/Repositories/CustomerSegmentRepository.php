<?php

namespace App\Modules\CRMManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\CRMManagement\Models\CustomerSegment;

class CustomerSegmentRepository extends BaseRepository
{
    public function __construct(CustomerSegment $model)
    {
        parent::__construct($model);
    }

    /**
     * Search customer segments by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['segment_type'])) {
            $query->where('segment_type', $criteria['segment_type']);
        }

        if (!empty($criteria['is_active'])) {
            $query->where('is_active', $criteria['is_active']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->withCount('customers')
            ->orderBy('name')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get active segments
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->withCount('customers')->get();
    }

    /**
     * Get segments by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('segment_type', $type)->withCount('customers')->get();
    }

    /**
     * Get segments with customers
     */
    public function getWithCustomers()
    {
        return $this->model->has('customers')->withCount('customers')->get();
    }

    /**
     * Get segment with customers
     */
    public function getSegmentWithCustomers(int $id)
    {
        return $this->model->with('customers')->findOrFail($id);
    }

    /**
     * Get customers for segment
     */
    public function getCustomersForSegment(int $segmentId)
    {
        $segment = $this->findOrFail($segmentId);
        return $segment->customers;
    }
}
