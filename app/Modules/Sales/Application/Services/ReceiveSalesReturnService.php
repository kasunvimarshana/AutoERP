<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Sales\Domain\Entities\SalesReturn;
use Modules\Sales\Domain\Events\SalesReturnReceived;
use Modules\Sales\Domain\Exceptions\SalesReturnNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;

class ReceiveSalesReturnService extends BaseService implements ReceiveSalesReturnServiceInterface
{
    public function __construct(private readonly SalesReturnRepositoryInterface $salesReturnRepository)
    {
        parent::__construct($salesReturnRepository);
    }

    protected function handle(array $data): SalesReturn
    {
        $id = (int) ($data['id'] ?? 0);
        $return = $this->salesReturnRepository->find($id);

        if (! $return) {
            throw new SalesReturnNotFoundException($id);
        }

        $return->receive();
        $saved = $this->salesReturnRepository->save($return);

        $this->addEvent(new SalesReturnReceived(
            tenantId: $saved->getTenantId(),
            salesReturnId: (int) $saved->getId(),
            customerId: $saved->getCustomerId(),
            lines: array_map(fn ($l) => [
                'id' => $l->getId(),
                'product_id' => $l->getProductId(),
                'to_location_id' => $l->getToLocationId(),
                'uom_id' => $l->getUomId(),
                'return_qty' => $l->getReturnQty(),
                'variant_id' => $l->getVariantId(),
                'batch_id' => $l->getBatchId(),
                'serial_id' => $l->getSerialId(),
            ], $saved->getLines()),
        ));

        return $saved;
    }
}
