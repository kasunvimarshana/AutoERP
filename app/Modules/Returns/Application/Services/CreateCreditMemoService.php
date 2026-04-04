<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Returns\Application\DTOs\CreditMemoData;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\Events\CreditMemoCreated;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\CreditMemoStatus;

class CreateCreditMemoService implements CreateCreditMemoServiceInterface
{
    public function __construct(
        private readonly CreditMemoRepositoryInterface $repository,
    ) {}

    public function execute(CreditMemoData $data): CreditMemo
    {
        $memo = $this->repository->create([
            'tenant_id'       => $data->tenantId,
            'stock_return_id' => $data->stockReturnId,
            'memo_number'     => $data->memoNumber,
            'amount'          => $data->amount,
            'status'          => CreditMemoStatus::DRAFT,
            'customer_id'     => $data->customerId,
            'currency'        => $data->currency ?? 'USD',
            'notes'           => $data->notes,
        ]);

        Event::dispatch(new CreditMemoCreated($memo->tenantId, $memo->id));

        return $memo;
    }
}
