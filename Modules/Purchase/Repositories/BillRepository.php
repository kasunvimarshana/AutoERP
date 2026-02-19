<?php

declare(strict_types=1);

namespace Modules\Purchase\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Purchase\Enums\BillStatus;
use Modules\Purchase\Exceptions\BillNotFoundException;
use Modules\Purchase\Models\Bill;

/**
 * Bill Repository
 *
 * Handles data access operations for vendor bills.
 */
class BillRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new Bill;
    }

    /**
     * Find bill by bill code.
     */
    public function findByBillCode(string $billCode): ?Bill
    {
        return $this->model->where('bill_code', $billCode)->first();
    }

    /**
     * Find bill by bill code or fail.
     */
    public function findByBillCodeOrFail(string $billCode): Bill
    {
        $bill = $this->findByBillCode($billCode);

        if (! $bill) {
            throw new BillNotFoundException("Bill with code {$billCode} not found");
        }

        return $bill;
    }

    /**
     * Get bills by vendor.
     */
    public function getByVendor(string $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('vendor_id', $vendorId)
            ->with(['vendor', 'items', 'payments'])
            ->latest('bill_date')
            ->paginate($perPage);
    }

    /**
     * Get overdue bills.
     */
    public function getOverdue(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereIn('status', [BillStatus::SENT, BillStatus::UNPAID, BillStatus::PARTIALLY_PAID, BillStatus::OVERDUE])
            ->where('due_date', '<', now())
            ->with(['vendor', 'items', 'payments'])
            ->latest('due_date')
            ->paginate($perPage);
    }

    /**
     * Get unpaid bills.
     */
    public function getUnpaid(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereIn('status', [BillStatus::SENT, BillStatus::UNPAID, BillStatus::PARTIALLY_PAID, BillStatus::OVERDUE])
            ->with(['vendor', 'items', 'payments'])
            ->latest('bill_date')
            ->paginate($perPage);
    }

    /**
     * Search bills with filters.
     */
    public function searchBills(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['vendor', 'items', 'payments']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('bill_code', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('vendor_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('bill_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('bill_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['due_from'])) {
            $query->where('due_date', '>=', $filters['due_from']);
        }

        if (! empty($filters['due_to'])) {
            $query->where('due_date', '<=', $filters['due_to']);
        }

        if (! empty($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->whereIn('status', [BillStatus::SENT, BillStatus::UNPAID, BillStatus::PARTIALLY_PAID, BillStatus::OVERDUE])
                ->where('due_date', '<', now());
        }

        return $query->latest('bill_date')->paginate($perPage);
    }

    /**
     * Get total outstanding amount across all unpaid bills.
     */
    public function getTotalOutstanding(): string
    {
        $total = $this->model
            ->whereIn('status', [BillStatus::SENT, BillStatus::UNPAID, BillStatus::PARTIALLY_PAID, BillStatus::OVERDUE])
            ->selectRaw('SUM(total_amount - paid_amount) as outstanding')
            ->value('outstanding');

        return $total ?? '0.000000';
    }

    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['vendor', 'purchaseOrder', 'goodsReceipt', 'items.product', 'items.unit']);

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (! empty($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', $filters['purchase_order_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('bill_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('bill_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['overdue'])) {
            $query->where('due_date', '<', now())
                ->whereIn('status', [BillStatus::SENT, BillStatus::PARTIALLY_PAID]);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('bill_code', 'like', "%{$search}%")
                    ->orWhere('vendor_invoice_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('bill_date')->paginate($perPage);
    }

    /**
     * Update bill and return the updated model.
     */
    public function update(int|string $id, array $data): Bill
    {
        $bill = $this->findOrFail($id);
        $bill->update($data);

        return $bill->fresh(['vendor', 'items', 'payments']);
    }
}
