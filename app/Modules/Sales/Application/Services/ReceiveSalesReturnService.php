<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Sales\Domain\Entities\SalesReturn;
use Modules\Sales\Domain\Events\SalesReturnReceived;
use Modules\Sales\Domain\Exceptions\SalesReturnNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;

class ReceiveSalesReturnService extends BaseService implements ReceiveSalesReturnServiceInterface
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $salesReturnRepository,
        private readonly SalesInvoiceRepositoryInterface $salesInvoiceRepository,
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
    ) {
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

        // Resolve AR account from original invoice when available
        $arAccountId = null;
        if ($saved->getOriginalInvoiceId() !== null) {
            $originalInvoice = $this->salesInvoiceRepository->find($saved->getOriginalInvoiceId());
            if ($originalInvoice !== null) {
                $arAccountId = $originalInvoice->getArAccountId();
            }
        }

        $incomeAccountMap = [];
        if ($saved->getOriginalSalesOrderId() !== null) {
            $originalSalesOrder = $this->salesOrderRepository->find($saved->getOriginalSalesOrderId());
            if ($originalSalesOrder !== null) {
                foreach ($originalSalesOrder->getLines() as $originalLine) {
                    if ($originalLine->getId() === null) {
                        continue;
                    }

                    $incomeAccountMap[$originalLine->getId()] = $originalLine->getIncomeAccountId();
                }
            }
        }

        $this->addEvent(new SalesReturnReceived(
            tenantId: $saved->getTenantId(),
            salesReturnId: (int) $saved->getId(),
            customerId: $saved->getCustomerId(),
            arAccountId: $arAccountId,
            grandTotal: $saved->getGrandTotal(),
            currencyId: $saved->getCurrencyId(),
            exchangeRate: $saved->getExchangeRate(),
            returnDate: $saved->getReturnDate()->format('Y-m-d'),
            lines: array_map(fn ($l) => [
                'id' => $l->getId(),
                'product_id' => $l->getProductId(),
                'to_location_id' => $l->getToLocationId(),
                'uom_id' => $l->getUomId(),
                'return_qty' => $l->getReturnQty(),
                'variant_id' => $l->getVariantId(),
                'batch_id' => $l->getBatchId(),
                'serial_id' => $l->getSerialId(),
                'income_account_id' => $incomeAccountMap[$l->getOriginalSalesOrderLineId() ?? 0] ?? null,
                'line_total' => $l->getLineTotal(),
            ], $saved->getLines()),
            createdBy: (int) (Auth::id() ?? 0),
        ));

        return $saved;
    }
}
