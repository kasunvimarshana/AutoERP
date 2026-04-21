<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Sales\Domain\Entities\SalesReturn;
use Modules\Sales\Domain\Exceptions\SalesReturnNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;

class ReceiveSalesReturnService extends BaseService implements ReceiveSalesReturnServiceInterface
{
    public function __construct(private readonly SalesReturnRepositoryInterface $salesReturnRepository)
    {
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

        return $this->salesReturnRepository->save($return);
    }
}
