<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\IssueCreditMemoDocumentServiceInterface;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\Events\CreditMemoIssued;
use Modules\Returns\Domain\Exceptions\CreditMemoNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class IssueCreditMemoDocumentService extends BaseService implements IssueCreditMemoDocumentServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): CreditMemo
    {
        $id   = $data['id'];
        $memo = $this->creditMemoRepository->find($id);

        if (! $memo) {
            throw new CreditMemoNotFoundException($id);
        }

        $memo->issue();

        $saved = $this->creditMemoRepository->save($memo);
        $this->addEvent(new CreditMemoIssued($saved));

        return $saved;
    }
}
