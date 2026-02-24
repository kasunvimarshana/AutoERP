<?php

namespace Modules\Inventory\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Inventory\Domain\Contracts\InventoryLotRepositoryInterface;
use Modules\Inventory\Domain\Events\LotCreated;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class CreateLotUseCase implements UseCaseInterface
{
    public function __construct(
        private InventoryLotRepositoryInterface $lotRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $lotNumber    = trim($data['lot_number'] ?? '');
        $tenantId     = $data['tenant_id'];
        $productId    = $data['product_id'];
        $trackingType = $data['tracking_type'] ?? 'lot';
        $qty          = (string) ($data['qty'] ?? '1');

        if ($lotNumber === '') {
            throw new DomainException('Lot number must not be empty.');
        }

        if (bccomp($qty, '0', 8) <= 0) {
            throw new DomainException('Lot quantity must be greater than zero.');
        }

        if ($this->lotRepo->findByLotNumber($tenantId, $productId, $lotNumber) !== null) {
            throw new DomainException("Lot number '{$lotNumber}' already exists for this product.");
        }

        return DB::transaction(function () use ($data, $tenantId, $productId, $lotNumber, $trackingType, $qty) {
            $lot = $this->lotRepo->create([
                'id'               => (string) Str::uuid(),
                'tenant_id'        => $tenantId,
                'product_id'       => $productId,
                'lot_number'       => $lotNumber,
                'tracking_type'    => $trackingType,
                'qty'              => bcadd($qty, '0.00000000', 8),
                'status'           => 'active',
                'manufacture_date' => $data['manufacture_date'] ?? null,
                'expiry_date'      => $data['expiry_date'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            Event::dispatch(new LotCreated(
                lotId:        $lot->id,
                tenantId:     $tenantId,
                productId:    $productId,
                lotNumber:    $lotNumber,
                trackingType: $trackingType,
            ));

            return $lot;
        });
    }
}
