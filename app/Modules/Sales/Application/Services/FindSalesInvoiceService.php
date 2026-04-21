<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\FindSalesInvoiceServiceInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;

class FindSalesInvoiceService extends BaseService implements FindSalesInvoiceServiceInterface
{
    public function __construct(private readonly SalesInvoiceRepositoryInterface $salesInvoiceRepository)
    {
        parent::__construct($salesInvoiceRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
