<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
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
        if ($dto->category_rule_id !== null) {
            $bt->categorize($dto->category_rule_id);
        }
        if ($dto->matched_journal_entry_id !== null) {
            $bt->matchToJournalEntry($dto->matched_journal_entry_id);
        }

        return $this->bankTransactionRepository->save($bt);
    }
}
