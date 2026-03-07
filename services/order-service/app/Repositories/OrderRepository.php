<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
        $this->eagerLoads = ['items'];
    }

    // -------------------------------------------------------------------------
    // OrderRepositoryInterface
    // -------------------------------------------------------------------------

    public function getByTenant(string $tenantId, Request $request): Collection|LengthAwarePaginator
    {
        $query = $this->newQuery()->tenant($tenantId);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_email', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by payment_status
        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        // Filter by date range
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Sorting
        $sortBy  = in_array($request->input('sort_by'), ['created_at', 'total', 'order_number', 'status'], true)
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $this->paginateConditional($query, $request);
    }

    public function getByStatus(string $tenantId, string $status): Collection
    {
        return $this->newQuery()
            ->tenant($tenantId)
            ->status($status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByCustomer(string $tenantId, string $customerId, Request $request): Collection|LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->tenant($tenantId)
            ->forCustomer($customerId)
            ->orderBy('created_at', 'desc');

        return $this->paginateConditional($query, $request);
    }

    public function findByOrderNumber(string $tenantId, string $orderNumber): ?Order
    {
        /** @var Order|null */
        return $this->newQuery()
            ->tenant($tenantId)
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function createWithItems(array $orderData, array $items): Order
    {
        return DB::transaction(function () use ($orderData, $items): Order {
            /** @var Order $order */
            $order = $this->model->newQuery()->create($orderData);

            foreach ($items as $item) {
                $item['order_id'] = $order->id;
                $order->items()->create($item);
            }

            return $order->load('items');
        });
    }
}
