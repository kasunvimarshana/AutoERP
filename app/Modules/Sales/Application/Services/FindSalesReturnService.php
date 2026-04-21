<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\FindSalesReturnServiceInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;

class FindSalesReturnService extends BaseService implements FindSalesReturnServiceInterface
{
    public function __construct(private readonly SalesReturnRepositoryInterface $salesReturnRepository)
    {
        parent::__construct($salesReturnRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
