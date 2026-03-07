<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Order;

use App\Domain\Order\Contracts\OrderRepositoryInterface;
use App\Infrastructure\Repositories\BaseRepository;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the Order repository.
 */
class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    protected array $filterable = [
        'status',
        'customer_id',
        'tenant_id',
        'currency',
    ];

    protected array $searchable = [
        'order_number',
        'customer_name',
        'customer_email',
    ];

    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function findByOrderNumber(string $orderNumber): ?Model
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function findByCustomer(int|string $customerId): Collection
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(int|string $orderId, string $status, array $metadata = []): Model
    {
        return DB::transaction(function () use ($orderId, $status, $metadata): Model {
            /** @var \App\Domain\Order\Entities\Order $order */
            $order = $this->applyTenantScope($this->model->newQuery())
                ->lockForUpdate()
                ->findOrFail($orderId);

            $order->transitionTo($status, $metadata);

            return $order->fresh(['items']);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function attachItems(int|string $orderId, array $items): Model
    {
        return DB::transaction(function () use ($orderId, $items): Model {
            $order = $this->findOrFail($orderId);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id'  => $item['product_id'],
                    'sku'         => $item['sku'] ?? null,
                    'name'        => $item['name'] ?? null,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'tax_rate'    => $item['tax_rate'] ?? 0,
                    'discount'    => $item['discount'] ?? 0,
                    'metadata'    => $item['metadata'] ?? null,
                ]);
            }

            $order->recalculateTotals();

            return $order->fresh(['items']);
        });
    }

    public function statusSummary(): Collection
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
    }
}
