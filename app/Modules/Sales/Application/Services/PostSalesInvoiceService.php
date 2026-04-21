<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\Sales\Domain\Entities\SalesInvoice;
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

        return $this->salesInvoiceRepository->save($invoice);
    }
}
