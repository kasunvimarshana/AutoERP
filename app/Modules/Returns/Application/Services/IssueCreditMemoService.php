<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnCreditMemoIssued;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;

class IssueCreditMemoService implements IssueCreditMemoServiceInterface
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $repository,
    ) {}

    public function execute(StockReturn $return): StockReturn
    {
        $creditMemoNumber = 'CM-' . strtoupper($return->returnNumber);

        $updated = $this->repository->update($return, [
            'credit_memo_number' => $creditMemoNumber,
        ]);

        Event::dispatch(new StockReturnCreditMemoIssued($updated->tenantId, $updated->id));

        return $updated;
    }
}
