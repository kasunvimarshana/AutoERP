<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentModel;

class EloquentPaymentRepository extends EloquentRepository implements PaymentRepositoryInterface
{
    public function __construct(PaymentModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PaymentModel $model): Payment => $this->mapModelToDomainEntity($model));
    }

    public function save(Payment $payment): Payment
    {
        $data = [
            'tenant_id' => $payment->getTenantId(),
            'payment_number' => $payment->getPaymentNumber(),
            'direction' => $payment->getDirection(),
            'party_type' => $payment->getPartyType(),
            'party_id' => $payment->getPartyId(),
            'payment_method_id' => $payment->getPaymentMethodId(),
            'account_id' => $payment->getAccountId(),
            'amount' => $payment->getAmount(),
            'currency_id' => $payment->getCurrencyId(),
            'exchange_rate' => $payment->getExchangeRate(),
            'base_amount' => $payment->getBaseAmount(),
            'payment_date' => $payment->getPaymentDate()->format('Y-m-d'),
            'status' => $payment->getStatus(),
            'reference' => $payment->getReference(),
            'notes' => $payment->getNotes(),
            'idempotency_key' => $payment->getIdempotencyKey(),
            'journal_entry_id' => $payment->getJournalEntryId(),
            'row_version' => $payment->getRowVersion(),
        ];

        if ($payment->getId()) {
            $model = $this->update($payment->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var PaymentModel $model */
        return $this->toDomainEntity($model);
    }

    public function findByTenantAndNumber(int $tenantId, string $paymentNumber): ?Payment
    {
        /** @var PaymentModel|null $model */
        $model = $this->model->newQueryWithoutScopes()
            ->where('tenant_id', $tenantId)
            ->where('payment_number', $paymentNumber)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndIdempotencyKey(int $tenantId, string $idempotencyKey): ?Payment
    {
        /** @var PaymentModel|null $model */
        $model = $this->model->newQueryWithoutScopes()
            ->where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(PaymentModel $model): Payment
    {
        return new Payment(
            tenantId: (int) $model->tenant_id,
            paymentNumber: (string) $model->payment_number,
            direction: (string) $model->direction,
            partyType: (string) $model->party_type,
            partyId: (int) $model->party_id,
            paymentMethodId: (int) $model->payment_method_id,
            accountId: (int) $model->account_id,
            amount: (float) $model->amount,
            currencyId: (int) $model->currency_id,
            paymentDate: $model->payment_date,
            exchangeRate: (float) $model->exchange_rate,
            baseAmount: (float) $model->base_amount,
            status: (string) $model->status,
            reference: $model->reference,
            notes: $model->notes,
            idempotencyKey: $model->idempotency_key,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            rowVersion: (int) ($model->row_version ?? 1),
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
