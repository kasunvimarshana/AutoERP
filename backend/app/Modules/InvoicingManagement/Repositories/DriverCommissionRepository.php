<?php

namespace App\Modules\InvoicingManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InvoicingManagement\Models\DriverCommission;

class DriverCommissionRepository extends BaseRepository
{
    public function __construct(DriverCommission $model)
    {
        parent::__construct($model);
    }

    /**
     * Search driver commissions by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('commission_number', 'like', "%{$search}%")
                    ->orWhereHas('driver', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['driver_id'])) {
            $query->where('driver_id', $criteria['driver_id']);
        }

        if (!empty($criteria['invoice_id'])) {
            $query->where('invoice_id', $criteria['invoice_id']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('commission_date', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('commission_date', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['driver', 'invoice'])
            ->orderBy('commission_date', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get commissions by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['driver', 'invoice'])->get();
    }

    /**
     * Get commissions for driver
     */
    public function getForDriver(int $driverId)
    {
        return $this->model->where('driver_id', $driverId)
            ->with(['invoice'])
            ->orderBy('commission_date', 'desc')
            ->get();
    }

    /**
     * Get commissions for invoice
     */
    public function getForInvoice(int $invoiceId)
    {
        return $this->model->where('invoice_id', $invoiceId)
            ->with(['driver'])
            ->get();
    }

    /**
     * Get pending commissions
     */
    public function getPending()
    {
        return $this->model->where('status', 'pending')->with(['driver', 'invoice'])->get();
    }

    /**
     * Get paid commissions
     */
    public function getPaid()
    {
        return $this->model->where('status', 'paid')->with(['driver', 'invoice'])->get();
    }

    /**
     * Get commissions by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->model->whereBetween('commission_date', [$startDate, $endDate])
            ->with(['driver', 'invoice'])
            ->orderBy('commission_date', 'desc')
            ->get();
    }
}
