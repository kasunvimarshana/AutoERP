<?php

declare(strict_types=1);

namespace Modules\Invoice\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Models\DriverCommission;

/**
 * DriverCommission Repository
 *
 * Handles data access for DriverCommission model
 */
class DriverCommissionRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new DriverCommission;
    }

    /**
     * Get commissions for driver
     */
    public function getForDriver(int $driverId): Collection
    {
        return $this->model->newQuery()
            ->where('driver_id', $driverId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get commissions for invoice
     */
    public function getForInvoice(int $invoiceId): Collection
    {
        return $this->model->newQuery()->where('invoice_id', $invoiceId)->get();
    }

    /**
     * Get commissions by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()->where('status', $status)->get();
    }

    /**
     * Get pending commissions
     */
    public function getPending(): Collection
    {
        return $this->model->newQuery()->pending()->get();
    }

    /**
     * Get paid commissions
     */
    public function getPaid(): Collection
    {
        return $this->model->newQuery()->paid()->get();
    }

    /**
     * Get commission with relations
     */
    public function findWithRelations(int $id): ?DriverCommission
    {
        /** @var DriverCommission|null */
        return $this->model->newQuery()
            ->with(['invoice', 'driver', 'approvedBy'])
            ->find($id);
    }

    /**
     * Get commissions with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function getWithFilters(array $filters = []): Collection
    {
        $query = $this->model->newQuery()->with(['invoice', 'driver']);

        if (isset($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereHas('invoice', function ($q) use ($filters) {
                $q->where('invoice_date', '>=', $filters['from_date']);
            });
        }

        if (isset($filters['to_date'])) {
            $query->whereHas('invoice', function ($q) use ($filters) {
                $q->where('invoice_date', '<=', $filters['to_date']);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
