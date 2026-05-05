<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\PostPurchaseReturnServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseReturn;
use Modules\Purchase\Domain\Events\PurchaseReturnPosted;
use Modules\Purchase\Domain\Exceptions\PurchaseReturnNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;

class PostPurchaseReturnService extends BaseService implements PostPurchaseReturnServiceInterface
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repo,
        private readonly PurchaseReturnLineRepositoryInterface $purchaseReturnLineRepository,
        private readonly PurchaseInvoiceRepositoryInterface $purchaseInvoiceRepository,
        private readonly PurchaseInvoiceLineRepositoryInterface $purchaseInvoiceLineRepository,
    ) {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseReturn
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseReturnNotFoundException($id);
        }

        if ($entity->getStatus() !== 'draft') {
            throw new \InvalidArgumentException('Purchase return cannot be posted in its current state.');
        }

        $entity->post();
        $saved = $this->repo->save($entity);

        $lines = $this->purchaseReturnLineRepository->findByPurchaseReturnId((int) $saved->getId());

        // Resolve financial data from original invoice when available
        $apAccountId = null;
        $currencyId = $saved->getCurrencyId();
        $exchangeRate = $saved->getExchangeRate();
        $productAccountMap = [];

        if ($saved->getOriginalInvoiceId() !== null) {
            $originalInvoice = $this->purchaseInvoiceRepository->find($saved->getOriginalInvoiceId());
            if ($originalInvoice !== null) {
                $apAccountId = $originalInvoice->getApAccountId();
                $currencyId = $originalInvoice->getCurrencyId();
                $exchangeRate = $originalInvoice->getExchangeRate();

                $invoiceLines = $this->purchaseInvoiceLineRepository->findByInvoiceId((int) $originalInvoice->getId());
                foreach ($invoiceLines as $invoiceLine) {
                    $productAccountMap[$invoiceLine->getProductId()] = $invoiceLine->getAccountId();
                }
            }
        }

        $createdBy = (int) ($data['created_by'] ?? Auth::id() ?? 0);

        $eventLines = $lines->map(fn ($l) => [
            'id' => $l->getId(),
            'product_id' => $l->getProductId(),
            'from_location_id' => $l->getFromLocationId(),
            'uom_id' => $l->getUomId(),
            'return_qty' => $l->getReturnQty(),
            'unit_cost' => $l->getUnitCost(),
            'variant_id' => $l->getVariantId(),
            'batch_id' => $l->getBatchId(),
            'serial_id' => $l->getSerialId(),
            'account_id' => $productAccountMap[$l->getProductId()] ?? null,
            'line_total' => bcmul($l->getReturnQty(), $l->getUnitCost(), 6),
            'tax_amount' => '0.000000',
        ])->values()->all();

        $this->addEvent(new PurchaseReturnPosted(
            tenantId: $saved->getTenantId(),
            purchaseReturnId: (int) $saved->getId(),
            supplierId: $saved->getSupplierId(),
            apAccountId: $apAccountId,
            grandTotal: $saved->getGrandTotal(),
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            returnDate: $saved->getReturnDate()->format('Y-m-d'),
            lines: $eventLines,
            createdBy: $createdBy,
        ));

        return $saved;
    }
}
