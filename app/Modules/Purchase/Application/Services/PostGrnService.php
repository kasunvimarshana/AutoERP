<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Purchase\Application\Contracts\PostGrnServiceInterface;
use Modules\Purchase\Domain\Entities\GrnHeader;
use Modules\Purchase\Domain\Events\GoodsReceiptPosted;
use Modules\Purchase\Domain\Exceptions\GrnNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnHeaderRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class PostGrnService extends BaseService implements PostGrnServiceInterface
{
    public function __construct(
        private readonly GrnHeaderRepositoryInterface $grnHeaderRepository,
        private readonly GrnLineRepositoryInterface $grnLineRepository,
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly PurchaseOrderLineRepositoryInterface $purchaseOrderLineRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
        parent::__construct($grnHeaderRepository);
    }

    protected function handle(array $data): GrnHeader
    {
        $id = (int) ($data['id'] ?? 0);
        $grnHeader = $this->grnHeaderRepository->find($id);

        if (! $grnHeader) {
            throw new GrnNotFoundException($id);
        }

        if (! in_array($grnHeader->getStatus(), ['draft', 'partial'], true)) {
            throw new \InvalidArgumentException('GRN cannot be posted in its current state.');
        }

        $lines = $this->grnLineRepository->findByGrnHeaderId($grnHeader->getTenantId(), $id);

        if ($grnHeader->getPurchaseOrderId() !== null) {
            foreach ($lines as $grnLine) {
                if ($grnLine->getPurchaseOrderLineId() !== null) {
                    $poLine = $this->purchaseOrderLineRepository->find($grnLine->getPurchaseOrderLineId());
                    if ($poLine && $poLine->getTenantId() === $grnHeader->getTenantId()) {
                        $poLine->addReceivedQty($grnLine->getReceivedQty());
                        $this->purchaseOrderLineRepository->save($poLine);
                    }
                }
            }
        }

        $grnHeader->post();
        $saved = $this->grnHeaderRepository->save($grnHeader);

        // Resolve supplier AP account for GL posting
        $supplier = $this->supplierRepository->find($saved->getSupplierId());
        $apAccountId = $supplier?->getApAccountId();

        // Resolve per-product inventory account IDs for GL posting
        $inventoryAccountMap = [];
        foreach ($lines as $line) {
            $productId = $line->getProductId();
            if (! isset($inventoryAccountMap[$productId])) {
                $product = $this->productRepository->find($productId);
                $inventoryAccountMap[$productId] = $product?->getInventoryAccountId();
            }
        }

        $this->addEvent(new GoodsReceiptPosted(
            tenantId: $saved->getTenantId(),
            grnHeaderId: (int) $saved->getId(),
            supplierId: $saved->getSupplierId(),
            warehouseId: $saved->getWarehouseId(),
            lines: $lines->map(fn ($l) => [
                'id' => $l->getId(),
                'product_id' => $l->getProductId(),
                'location_id' => $l->getLocationId(),
                'uom_id' => $l->getUomId(),
                'received_qty' => $l->getReceivedQty(),
                'unit_cost' => $l->getUnitCost(),
                'variant_id' => $l->getVariantId(),
                'batch_id' => $l->getBatchId(),
                'serial_id' => $l->getSerialId(),
                'inventory_account_id' => $inventoryAccountMap[$l->getProductId()] ?? null,
            ])->values()->all(),
            apAccountId: $apAccountId,
            currencyId: $saved->getCurrencyId(),
            exchangeRate: $saved->getExchangeRate(),
            receivedDate: $saved->getReceivedDate()->format('Y-m-d'),
            createdBy: (int) (Auth::id() ?? 0),
        ));

        return $saved;
    }
}
