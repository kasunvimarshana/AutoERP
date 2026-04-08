<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\DeleteStockReturnLineServiceInterface;
use Modules\Returns\Domain\Events\StockReturnLineDeleted;
use Modules\Returns\Domain\Exceptions\StockReturnLineNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;

class DeleteStockReturnLineService extends BaseService implements DeleteStockReturnLineServiceInterface
{
    public function __construct(private readonly StockReturnLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $line = $this->lineRepository->find($id);

        if (! $line) {
            throw new StockReturnLineNotFoundException($id);
        }

        $this->addEvent(new StockReturnLineDeleted($line));

        return $this->lineRepository->delete($id);
    }
}
