<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\DeleteStockReturnServiceInterface;
use Modules\Returns\Domain\Events\StockReturnDeleted;
use Modules\Returns\Domain\Exceptions\StockReturnNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;

class DeleteStockReturnService extends BaseService implements DeleteStockReturnServiceInterface
{
    public function __construct(private readonly StockReturnRepositoryInterface $returnRepository)
    {
        parent::__construct($returnRepository);
    }

    protected function handle(array $data): bool
    {
        $id     = $data['id'];
        $return = $this->returnRepository->find($id);

        if (! $return) {
            throw new StockReturnNotFoundException($id);
        }

        $this->addEvent(new StockReturnDeleted($return));

        return $this->returnRepository->delete($id);
    }
}
