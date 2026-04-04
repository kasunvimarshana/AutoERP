<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\Events\CreditMemoVoided;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\CreditMemoStatus;

class VoidCreditMemoService implements VoidCreditMemoServiceInterface
{
    public function __construct(
        private readonly CreditMemoRepositoryInterface $repository,
    ) {}

    public function execute(CreditMemo $memo): CreditMemo
    {
        if ($memo->status === CreditMemoStatus::APPLIED) {
            throw new \DomainException('Cannot void an already applied credit memo.');
        }

        $updated = $this->repository->update($memo, [
            'status' => CreditMemoStatus::VOIDED,
        ]);

        Event::dispatch(new CreditMemoVoided($updated->tenantId, $updated->id));

        return $updated;
    }
}
