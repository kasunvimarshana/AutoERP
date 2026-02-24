<?php

namespace Modules\POS\Infrastructure\Repositories;

use Illuminate\Support\Str;
use Modules\POS\Domain\Contracts\PosOrderPaymentRepositoryInterface;
use Modules\POS\Infrastructure\Models\PosOrderPaymentModel;

class PosOrderPaymentRepository implements PosOrderPaymentRepositoryInterface
{
    public function create(array $data): object
    {
        $data['id'] = $data['id'] ?? (string) Str::uuid();
        return PosOrderPaymentModel::create($data);
    }

    public function findByOrderId(string $orderId): array
    {
        return PosOrderPaymentModel::withoutGlobalScopes()
            ->where('order_id', $orderId)
            ->orderBy('created_at')
            ->get()
            ->all();
    }
}
