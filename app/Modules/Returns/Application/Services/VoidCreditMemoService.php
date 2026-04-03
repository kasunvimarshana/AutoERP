<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\Events\CreditMemoVoided;
use Modules\Returns\Domain\Exceptions\CreditMemoNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class VoidCreditMemoService extends BaseService implements VoidCreditMemoServiceInterface
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

        $memo->void();

        $saved = $this->creditMemoRepository->save($memo);
        $this->addEvent(new CreditMemoVoided($saved));

        return $saved;
    }
}
