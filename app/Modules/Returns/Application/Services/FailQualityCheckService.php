<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\FailQualityCheckServiceInterface;
use Modules\Returns\Domain\Entities\StockReturnLine;
use Modules\Returns\Domain\Events\StockReturnLineFailed;
use Modules\Returns\Domain\Exceptions\StockReturnLineNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;

class FailQualityCheckService extends BaseService implements FailQualityCheckServiceInterface
{
    public function __construct(private readonly StockReturnLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): StockReturnLine
    {
        $id   = (int) $data['id'];
        $line = $this->lineRepository->find($id);

        if (! $line) {
            throw new StockReturnLineNotFoundException($id);
        }

        $line->failQualityCheck((int) $data['checked_by']);

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new StockReturnLineFailed($saved));

        return $saved;
    }
}
