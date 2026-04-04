<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\IssueCreditMemoDirectServiceInterface;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\Events\CreditMemoIssued;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\CreditMemoStatus;

class IssueCreditMemoDirectService implements IssueCreditMemoDirectServiceInterface
{
    public function __construct(
        private readonly CreditMemoRepositoryInterface $repository,
    ) {}

    public function execute(CreditMemo $memo, int $issuedBy): CreditMemo
    {
        $updated = $this->repository->update($memo, [
            'status'    => CreditMemoStatus::ISSUED,
            'issued_at' => now(),
            'issued_by' => $issuedBy,
        ]);

        Event::dispatch(new CreditMemoIssued($updated->tenantId, $updated->id));

        return $updated;
    }
}
