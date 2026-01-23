<?php

namespace App\Modules\CRMManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\CRMManagement\Models\Communication;

class CommunicationRepository extends BaseRepository
{
    public function __construct(Communication $model)
    {
        parent::__construct($model);
    }

    /**
     * Search communications by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['communication_type'])) {
            $query->where('communication_type', $criteria['communication_type']);
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['customer_id'])) {
            $query->where('customer_id', $criteria['customer_id']);
        }

        if (!empty($criteria['direction'])) {
            $query->where('direction', $criteria['direction']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('sent_at', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('sent_at', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['customer'])
            ->orderBy('sent_at', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get communications by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('communication_type', $type)->with(['customer'])->get();
    }

    /**
     * Get communications by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['customer'])->get();
    }

    /**
     * Get communications for customer
     */
    public function getForCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)
            ->orderBy('sent_at', 'desc')
            ->get();
    }

    /**
     * Get sent communications
     */
    public function getSent()
    {
        return $this->model->where('status', 'sent')->with(['customer'])->get();
    }

    /**
     * Get pending communications
     */
    public function getPending()
    {
        return $this->model->where('status', 'pending')->with(['customer'])->get();
    }

    /**
     * Get failed communications
     */
    public function getFailed()
    {
        return $this->model->where('status', 'failed')->with(['customer'])->get();
    }

    /**
     * Get inbound communications
     */
    public function getInbound()
    {
        return $this->model->where('direction', 'inbound')->with(['customer'])->get();
    }

    /**
     * Get outbound communications
     */
    public function getOutbound()
    {
        return $this->model->where('direction', 'outbound')->with(['customer'])->get();
    }

    /**
     * Get communications by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->model->whereBetween('sent_at', [$startDate, $endDate])
            ->with(['customer'])
            ->orderBy('sent_at', 'desc')
            ->get();
    }
}
