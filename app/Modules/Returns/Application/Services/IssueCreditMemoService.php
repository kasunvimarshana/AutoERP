<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnCreditMemoIssued;
use Modules\Returns\Domain\Exceptions\StockReturnNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;

class IssueCreditMemoService extends BaseService implements IssueCreditMemoServiceInterface
{
    public function __construct(private readonly StockReturnRepositoryInterface $returnRepository)
    {
        parent::__construct($returnRepository);
    }

    protected function handle(array $data): StockReturn
    {
        $id        = $data['id'];
        $reference = $data['credit_memo_reference'];
        $return    = $this->returnRepository->find($id);

        if (! $return) {
            throw new StockReturnNotFoundException($id);
        }

        $return->issueCreditMemo($reference);

        $saved = $this->returnRepository->save($return);
        $this->addEvent(new StockReturnCreditMemoIssued($saved));

        return $saved;
    }
}
