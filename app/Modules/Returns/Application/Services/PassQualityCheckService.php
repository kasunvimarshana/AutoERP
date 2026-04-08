<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\PassQualityCheckServiceInterface;
use Modules\Returns\Domain\Entities\StockReturnLine;
use Modules\Returns\Domain\Events\StockReturnLinePassed;
use Modules\Returns\Domain\Exceptions\StockReturnLineNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;

class PassQualityCheckService extends BaseService implements PassQualityCheckServiceInterface
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

        $line->passQualityCheck((int) $data['checked_by']);

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new StockReturnLinePassed($saved));

        return $saved;
    }
}
