<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\DeleteSalesOrderLineServiceInterface;
use Modules\SalesOrder\Domain\Events\SalesOrderLineDeleted;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderLineNotFoundException;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;

class DeleteSalesOrderLineService extends BaseService implements DeleteSalesOrderLineServiceInterface
{
    public function __construct(private readonly SalesOrderLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $line = $this->lineRepository->find($id);

        if (! $line) {
            throw new SalesOrderLineNotFoundException($id);
        }

        $this->addEvent(new SalesOrderLineDeleted($id, $line->getSalesOrderId()));

        return $this->lineRepository->delete($id);
    }
}
