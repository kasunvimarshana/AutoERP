<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Application\Contracts\UpdateBankTransactionServiceInterface;
use Modules\Finance\Application\DTOs\BankTransactionData;
use Modules\Finance\Domain\Entities\BankTransaction;
use Modules\Finance\Domain\Exceptions\BankTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;

class UpdateBankTransactionService extends BaseService implements UpdateBankTransactionServiceInterface
{
    public function __construct(private readonly BankTransactionRepositoryInterface $bankTransactionRepository)
    {
        parent::__construct($bankTransactionRepository);
    }

    protected function handle(array $data): BankTransaction
    {
        $dto = BankTransactionData::fromArray($data);
        /** @var BankTransaction|null $bt */
        $bt = $this->bankTransactionRepository->find((int) $dto->id);
        if (! $bt) {
            throw new BankTransactionNotFoundException((int) $dto->id);
        }
        if ($dto->rowVersion !== $bt->getRowVersion()) {
            throw new ConcurrentModificationException('BankTransaction', (int) $dto->id);
        }
        if ($dto->categoryRuleId !== null) {
            $bt->categorize($dto->categoryRuleId);
        }
        if ($dto->matchedJournalEntryId !== null) {
            $bt->matchToJournalEntry($dto->matchedJournalEntryId);
        }

        return $this->bankTransactionRepository->save($bt);
    }
}
