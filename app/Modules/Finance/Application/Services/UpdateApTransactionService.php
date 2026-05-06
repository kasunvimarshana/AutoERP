<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Application\Contracts\UpdateApTransactionServiceInterface;
use Modules\Finance\Application\DTOs\ApTransactionData;
use Modules\Finance\Domain\Entities\ApTransaction;
use Modules\Finance\Domain\Exceptions\ApTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;

class UpdateApTransactionService extends BaseService implements UpdateApTransactionServiceInterface
{
    public function __construct(private readonly ApTransactionRepositoryInterface $apTransactionRepository)
    {
        parent::__construct($apTransactionRepository);
    }

    protected function handle(array $data): ApTransaction
    {
        $dto = ApTransactionData::fromArray($data);
        /** @var ApTransaction|null $ap */
        $ap = $this->apTransactionRepository->find((int) $dto->id);
        if (! $ap) {
            throw new ApTransactionNotFoundException((int) $dto->id);
        }
        if ($dto->rowVersion !== $ap->getRowVersion()) {
            throw new ConcurrentModificationException('ApTransaction', (int) $dto->id);
        }
        if ($dto->isReconciled) {
            $ap->reconcile();
        }

        return $this->apTransactionRepository->save($ap);
    }
}
