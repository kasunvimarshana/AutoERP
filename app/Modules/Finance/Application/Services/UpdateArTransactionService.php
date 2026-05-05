<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Application\Contracts\UpdateArTransactionServiceInterface;
use Modules\Finance\Application\DTOs\ArTransactionData;
use Modules\Finance\Domain\Entities\ArTransaction;
use Modules\Finance\Domain\Exceptions\ArTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;

class UpdateArTransactionService extends BaseService implements UpdateArTransactionServiceInterface
{
    public function __construct(private readonly ArTransactionRepositoryInterface $arTransactionRepository)
    {
        parent::__construct($arTransactionRepository);
    }

    protected function handle(array $data): ArTransaction
    {
        $dto = ArTransactionData::fromArray($data);
        /** @var ArTransaction|null $ar */
        $ar = $this->arTransactionRepository->find((int) $dto->id);
        if (! $ar) {
            throw new ArTransactionNotFoundException((int) $dto->id);
        }
        if ($dto->row_version !== $ar->getRowVersion()) {
            throw new ConcurrentModificationException('ArTransaction', (int) $dto->id);
        }
        if ($dto->is_reconciled) {
            $ar->reconcile();
        }

        return $this->arTransactionRepository->save($ar);
    }
}
