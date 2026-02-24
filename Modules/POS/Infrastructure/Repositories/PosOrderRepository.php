<?php

namespace Modules\POS\Infrastructure\Repositories;

use Illuminate\Support\Str;
use Modules\POS\Infrastructure\Models\PosOrderLineModel;
use Modules\POS\Infrastructure\Models\PosOrderModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class PosOrderRepository extends BaseEloquentRepository
{
    public function __construct()
    {
        parent::__construct(new PosOrderModel());
    }

    public function create(array $data): object
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        $data['id'] = $data['id'] ?? (string) Str::uuid();

        $order = $this->model->newQuery()->create($data);

        foreach ($lines as $line) {
            $line['id'] = $line['id'] ?? (string) Str::uuid();
            $line['pos_order_id'] = $order->id;
            PosOrderLineModel::create($line);
        }

        return $order->load('lines');
    }

    /** Columns that callers are allowed to filter on. */
    private const ALLOWED_FILTERS = ['tenant_id', 'session_id', 'customer_id', 'status', 'payment_method'];

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = $this->model->newQuery()->with('lines');
        foreach ($filters as $key => $value) {
            if ($value !== null && in_array($key, self::ALLOWED_FILTERS, true)) {
                $query->where($key, $value);
            }
        }
        return $query->paginate($perPage);
    }

    public function nextNumber(string $tenantId): string
    {
        $count = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        return 'POS-' . str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}
