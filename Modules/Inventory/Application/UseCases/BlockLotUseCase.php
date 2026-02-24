<?php

namespace Modules\Inventory\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Domain\Contracts\InventoryLotRepositoryInterface;
use Modules\Inventory\Domain\Events\LotBlocked;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class BlockLotUseCase implements UseCaseInterface
{
    public function __construct(
        private InventoryLotRepositoryInterface $lotRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $lotId = $data['lot_id'];

        $lot = $this->lotRepo->findById($lotId);

        if ($lot === null) {
            throw new ModelNotFoundException('Lot not found.');
        }

        if ($lot->status === 'blocked') {
            throw new DomainException('Lot is already blocked.');
        }

        return DB::transaction(function () use ($lot) {
            $updated = $this->lotRepo->update($lot->id, ['status' => 'blocked']);

            Event::dispatch(new LotBlocked(
                lotId:     $lot->id,
                tenantId:  $lot->tenant_id,
                productId: $lot->product_id,
                lotNumber: $lot->lot_number,
            ));

            return $updated;
        });
    }
}
