<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\ApplyCreditMemoServiceInterface;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\Events\CreditMemoApplied;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\CreditMemoStatus;

class ApplyCreditMemoService implements ApplyCreditMemoServiceInterface
{
    public function __construct(
        private readonly CreditMemoRepositoryInterface $repository,
    ) {}

    public function execute(CreditMemo $memo): CreditMemo
    {
        if ($memo->status !== CreditMemoStatus::ISSUED) {
            throw new \DomainException('Only issued credit memos can be applied.');
        }

        $updated = $this->repository->update($memo, [
            'status' => CreditMemoStatus::APPLIED,
        ]);

        Event::dispatch(new CreditMemoApplied($updated->tenantId, $updated->id));

        return $updated;
    }
}
