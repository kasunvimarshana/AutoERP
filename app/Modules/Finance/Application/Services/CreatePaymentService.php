<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Database\QueryException;
use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Finance\Application\DTOs\PaymentData;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;

class CreatePaymentService extends BaseService implements CreatePaymentServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
        parent::__construct($paymentRepository);
    }

    protected function handle(array $data): Payment
    {
        $dto = PaymentData::fromArray($data);
        $idempotencyKey = $dto->idempotency_key;

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = $this->paymentRepository->findByTenantAndIdempotencyKey(
                $dto->tenant_id,
                $idempotencyKey,
            );

            if ($existing !== null) {
                return $existing;
            }
        }

        $payment = new Payment(
            tenantId: $dto->tenant_id,
            paymentNumber: $dto->payment_number,
            direction: $dto->direction,
            partyType: $dto->party_type,
            partyId: $dto->party_id,
            paymentMethodId: $dto->payment_method_id,
            accountId: $dto->account_id,
            amount: $dto->amount,
            currencyId: $dto->currency_id,
            paymentDate: new \DateTimeImmutable($dto->payment_date),
            exchangeRate: $dto->exchange_rate,
            baseAmount: $dto->base_amount,
            status: $dto->status,
            reference: $dto->reference,
            notes: $dto->notes,
            idempotencyKey: $idempotencyKey,
            journalEntryId: $dto->journal_entry_id,
        );

        try {
            return $this->paymentRepository->save($payment);
        } catch (QueryException $exception) {
            if (! $this->isPaymentIdempotencyConflict($exception, $idempotencyKey)) {
                throw $exception;
            }

            $existing = $this->paymentRepository->findByTenantAndIdempotencyKey(
                $dto->tenant_id,
                $idempotencyKey,
            );

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function isPaymentIdempotencyConflict(QueryException $exception, ?string $idempotencyKey): bool
    {
        if ($idempotencyKey === null || $idempotencyKey === '') {
            return false;
        }

        $message = strtolower($exception->getMessage());
        $code = (string) $exception->getCode();

        $isUniqueViolation = $code === '23000'
            || $code === '23505'
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'unique constraint failed')
            || str_contains($message, 'unique violation');

        if (! $isUniqueViolation) {
            return false;
        }

        return str_contains($message, 'payments_tenant_idempotency_key_uk')
            || str_contains($message, 'idempotency_key')
            || str_contains($message, 'payments.tenant_id, payments.idempotency_key');
    }
}
