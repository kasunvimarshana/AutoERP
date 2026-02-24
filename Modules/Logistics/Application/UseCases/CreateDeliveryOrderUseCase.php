<?php

namespace Modules\Logistics\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Logistics\Domain\Contracts\DeliveryLineRepositoryInterface;
use Modules\Logistics\Domain\Contracts\DeliveryOrderRepositoryInterface;
use Modules\Logistics\Infrastructure\Repositories\DeliveryOrderRepository;

class CreateDeliveryOrderUseCase
{
    public function __construct(
        private DeliveryOrderRepositoryInterface $orderRepo,
        private DeliveryLineRepositoryInterface  $lineRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;
            $year     = now()->year;

            /** @var DeliveryOrderRepository $concreteRepo */
            $concreteRepo = $this->orderRepo;
            $count        = $concreteRepo->countByTenantAndYear($tenantId, $year);
            $sequence     = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
            $referenceNo  = "DO-{$year}-{$sequence}";

            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $order = $this->orderRepo->create(array_merge($data, [
                'tenant_id'    => $tenantId,
                'reference_no' => $referenceNo,
                'status'       => 'pending',
                'weight'       => bcadd($data['weight'] ?? '0', '0', 8),
                'shipping_cost' => bcadd($data['shipping_cost'] ?? '0', '0', 8),
            ]));

            foreach ($lines as $line) {
                $this->lineRepo->create([
                    'tenant_id'         => $tenantId,
                    'delivery_order_id' => $order->id,
                    'product_id'        => $line['product_id'],
                    'product_name'      => $line['product_name'],
                    'quantity'          => bcadd((string) $line['quantity'], '0', 8),
                    'unit'              => $line['unit'] ?? 'pcs',
                ]);
            }

            return $order->fresh();
        });
    }
}
