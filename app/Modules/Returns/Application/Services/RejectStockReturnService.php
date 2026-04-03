<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\RejectStockReturnServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnRejected;
use Modules\Returns\Domain\Exceptions\StockReturnNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;

class RejectStockReturnService extends BaseService implements RejectStockReturnServiceInterface
{
    public function __construct(private readonly StockReturnRepositoryInterface $returnRepository)
    {
        parent::__construct($returnRepository);
    }

    protected function handle(array $data): StockReturn
    {
        $id     = $data['id'];
        $return = $this->returnRepository->find($id);

        if (! $return) {
            throw new StockReturnNotFoundException($id);
        }

        $return->reject();

        $saved = $this->returnRepository->save($return);
        $this->addEvent(new StockReturnRejected($saved));

        return $saved;
    }
}
