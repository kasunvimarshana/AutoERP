<?php

declare(strict_types=1);

namespace Modules\Order\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Order\Application\Contracts\ReturnOrderServiceInterface;
use Modules\Order\Domain\Contracts\Repositories\ReturnOrderRepositoryInterface;
use Modules\Order\Domain\Events\ReturnOrderCreated;

class ReturnOrderService extends BaseService implements ReturnOrderServiceInterface
{
    public function __construct(ReturnOrderRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler.
     */
    protected function handle(array $data): mixed
    {
        return $this->createReturnOrder($data);
    }

    /**
     * Create a return order (sales or purchase) with its lines.
     */
    public function createReturnOrder(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];

            $subtotal = (float) array_sum(array_map(
                fn ($line) => (float) ($line['quantity_returned'] ?? 0) * (float) ($line['unit_price'] ?? 0),
                $lines,
            ));

            $orderData = array_merge(
                array_diff_key($data, ['lines' => null]),
                [
                    'subtotal'      => $subtotal,
                    'refund_amount' => max(0.0, $subtotal - (float) ($data['restocking_fee'] ?? 0)),
                ],
            );

            $returnOrder = $this->repository->create($orderData);

            foreach ($lines as $line) {
                DB::table('return_order_lines')->insert(array_merge($line, [
                    'id'              => \Illuminate\Support\Str::uuid(),
                    'tenant_id'       => $returnOrder->tenant_id,
                    'return_order_id' => $returnOrder->id,
                    'line_total'      => (float) ($line['quantity_returned'] ?? 0) * (float) ($line['unit_price'] ?? 0),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]));
            }

            $this->addEvent(new ReturnOrderCreated((int) ($returnOrder->tenant_id ?? 0), $returnOrder->id));
            $this->dispatchEvents();

            return $returnOrder;
        });
    }

    /**
     * Confirm a return order (changes status to 'confirmed').
     */
    public function confirmReturn(string $id): mixed
    {
        return DB::transaction(function () use ($id) {
            return $this->repository->update($id, ['status' => 'confirmed']);
        });
    }
}
