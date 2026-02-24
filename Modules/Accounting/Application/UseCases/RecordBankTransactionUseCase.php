<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\BankAccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\Events\BankTransactionRecorded;

class RecordBankTransactionUseCase
{
    public function __construct(
        private BankAccountRepositoryInterface     $bankAccountRepo,
        private BankTransactionRepositoryInterface $transactionRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $bankAccount = $this->bankAccountRepo->findById($data['bank_account_id']);
            if (! $bankAccount) {
                throw new \DomainException('Bank account not found.');
            }

            if (! $bankAccount->is_active) {
                throw new \DomainException('Cannot record transactions for an inactive bank account.');
            }

            // BCMath: amount must be positive
            if (bccomp((string) ($data['amount'] ?? '0'), '0.00000000', 8) <= 0) {
                throw new \DomainException('Transaction amount must be greater than zero.');
            }

            $transaction = $this->transactionRepo->create(array_merge($data, [
                'amount'    => bcadd((string) $data['amount'], '0.00000000', 8),
                'status'    => 'unreconciled',
            ]));

            Event::dispatch(new BankTransactionRecorded(
                $transaction->id,
                $data['tenant_id'] ?? null,
                $transaction->bank_account_id,
                $transaction->type,
                $transaction->amount,
            ));

            return $transaction;
        });
    }
}
