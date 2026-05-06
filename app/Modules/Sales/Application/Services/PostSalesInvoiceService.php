<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Domain\Events\SalesInvoicePosted;
use Modules\Sales\Domain\Exceptions\SalesInvoiceNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;

class PostSalesInvoiceService extends BaseService implements PostSalesInvoiceServiceInterface
{
    public function __construct(private readonly SalesInvoiceRepositoryInterface $salesInvoiceRepository)
    {
        parent::__construct($salesInvoiceRepository);
    }

    protected function handle(array $data): SalesInvoice
    {
        $id = (int) ($data['id'] ?? 0);
        $invoice = $this->salesInvoiceRepository->find($id);

        if (! $invoice) {
            throw new SalesInvoiceNotFoundException($id);
        }

        $invoice->post();
        $saved = $this->salesInvoiceRepository->save($invoice);

        $lines = array_map(static fn ($line): array => [
            'income_account_id' => $line->getIncomeAccountId(),
            'line_total' => $line->getLineTotal(),
            'tax_amount' => $line->getTaxAmount(),
        ], $saved->getLines());

        $this->addEvent(new SalesInvoicePosted(
            tenantId: $saved->getTenantId(),
            salesInvoiceId: (int) $saved->getId(),
            customerId: $saved->getCustomerId(),
            arAccountId: $saved->getArAccountId(),
            grandTotal: $saved->getGrandTotal(),
            currencyId: $saved->getCurrencyId(),
            exchangeRate: $saved->getExchangeRate(),
            invoiceDate: $saved->getInvoiceDate()->format('Y-m-d'),
            lines: $lines,
            createdBy: (int) ($data['posted_by'] ?? Auth::id() ?? 0),
        ));

        return $saved;
    }
}
