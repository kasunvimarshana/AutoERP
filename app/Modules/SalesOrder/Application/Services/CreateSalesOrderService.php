<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class CreateSalesOrderService implements CreateSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repo) {}

    public function execute(array $data, array $lines): SalesOrder
    {
        $data['status'] = 'draft';
        return $this->repo->create($data, $lines);
    }
}
