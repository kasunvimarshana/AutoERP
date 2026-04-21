<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\DeleteSalesInvoiceServiceInterface;
use Modules\Sales\Domain\Exceptions\SalesInvoiceNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;

class DeleteSalesInvoiceService extends BaseService implements DeleteSalesInvoiceServiceInterface
{
    public function __construct(private readonly SalesInvoiceRepositoryInterface $salesInvoiceRepository)
    {
        parent::__construct($salesInvoiceRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $invoice = $this->salesInvoiceRepository->find($id);

        if (! $invoice) {
            throw new SalesInvoiceNotFoundException($id);
        }

        if ($invoice->getStatus() !== 'draft') {
            throw new \InvalidArgumentException('Only draft sales invoices can be deleted.');
        }

        return $this->salesInvoiceRepository->delete($id);
    }
}
