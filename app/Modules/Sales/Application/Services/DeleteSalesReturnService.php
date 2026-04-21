<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\DeleteSalesReturnServiceInterface;
use Modules\Sales\Domain\Exceptions\SalesReturnNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;

class DeleteSalesReturnService extends BaseService implements DeleteSalesReturnServiceInterface
{
    public function __construct(private readonly SalesReturnRepositoryInterface $salesReturnRepository)
    {
        parent::__construct($salesReturnRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $return = $this->salesReturnRepository->find($id);

        if (! $return) {
            throw new SalesReturnNotFoundException($id);
        }

        if ($return->getStatus() !== 'draft') {
            throw new \InvalidArgumentException('Only draft sales returns can be deleted.');
        }

        return $this->salesReturnRepository->delete($id);
    }
}
