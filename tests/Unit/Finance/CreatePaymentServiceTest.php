<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use Illuminate\Database\QueryException;
use Modules\Finance\Application\Services\CreatePaymentService;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use PDOException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class CreatePaymentServiceTest extends TestCase
{
    /** @var PaymentRepositoryInterface&MockObject */
    private PaymentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PaymentRepositoryInterface::class);
    }

    public function test_create_payment_service_returns_existing_payment_when_idempotency_key_matches(): void
    {
        $service = new CreatePaymentService($this->repository);
        $existing = $this->makePayment(id: 42, paymentNumber: 'PAY-00042', idempotencyKey: 'replay-key-1');

        $this->repository
            ->expects($this->once())
            ->method('findByTenantAndIdempotencyKey')
            ->with(1, 'replay-key-1')
            ->willReturn($existing);

        $this->repository
            ->expects($this->never())
            ->method('save');

        $result = $service->execute($this->makePayload([
            'idempotency_key' => 'replay-key-1',
            'payment_number' => 'PAY-NEW-IGNORED',
        ]));

        $this->assertSame($existing, $result);
        $this->assertSame(42, $result->getId());
        $this->assertSame('PAY-00042', $result->getPaymentNumber());
        $this->assertSame('replay-key-1', $result->getIdempotencyKey());
    }

    public function test_create_payment_service_saves_new_payment_when_idempotency_key_is_new(): void
    {
        $service = new CreatePaymentService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('findByTenantAndIdempotencyKey')
            ->with(1, 'new-key-1')
            ->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Payment $payment): bool {
                $this->assertSame(1, $payment->getTenantId());
                $this->assertSame('PAY-00001', $payment->getPaymentNumber());
                $this->assertSame('outbound', $payment->getDirection());
                $this->assertSame('supplier', $payment->getPartyType());
                $this->assertSame(15, $payment->getPartyId());
                $this->assertSame(2, $payment->getPaymentMethodId());
                $this->assertSame(5, $payment->getAccountId());
                $this->assertSame(120.5, $payment->getAmount());
                $this->assertSame(1, $payment->getCurrencyId());
                $this->assertSame('new-key-1', $payment->getIdempotencyKey());

                return true;
            }))
            ->willReturnCallback(static fn (Payment $payment): Payment => $payment);

        $result = $service->execute($this->makePayload([
            'idempotency_key' => 'new-key-1',
        ]));

        $this->assertSame('new-key-1', $result->getIdempotencyKey());
        $this->assertSame('PAY-00001', $result->getPaymentNumber());
    }

    public function test_create_payment_service_skips_idempotency_lookup_when_key_is_absent(): void
    {
        $service = new CreatePaymentService($this->repository);

        $this->repository
            ->expects($this->never())
            ->method('findByTenantAndIdempotencyKey');

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(static fn (Payment $payment): Payment => $payment);

        $result = $service->execute($this->makePayload([
            'idempotency_key' => null,
        ]));

        $this->assertNull($result->getIdempotencyKey());
        $this->assertSame('PAY-00001', $result->getPaymentNumber());
    }

    public function test_create_payment_service_returns_existing_payment_when_save_hits_idempotency_race(): void
    {
        $service = new CreatePaymentService($this->repository);
        $existing = $this->makePayment(id: 77, paymentNumber: 'PAY-00077', idempotencyKey: 'race-key-1');

        $this->repository
            ->expects($this->exactly(2))
            ->method('findByTenantAndIdempotencyKey')
            ->with(1, 'race-key-1')
            ->willReturnOnConsecutiveCalls(null, $existing);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willThrowException($this->makeIdempotencyQueryException());

        $result = $service->execute($this->makePayload([
            'idempotency_key' => 'race-key-1',
            'payment_number' => 'PAY-RACE-IGNORED',
        ]));

        $this->assertSame($existing, $result);
        $this->assertSame(77, $result->getId());
        $this->assertSame('PAY-00077', $result->getPaymentNumber());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function makePayload(array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => 1,
            'payment_number' => 'PAY-00001',
            'direction' => 'outbound',
            'party_type' => 'supplier',
            'party_id' => 15,
            'payment_method_id' => 2,
            'account_id' => 5,
            'amount' => 120.5,
            'currency_id' => 1,
            'payment_date' => '2026-04-28',
            'exchange_rate' => 1.0,
            'base_amount' => 120.5,
            'status' => 'draft',
            'reference' => 'INV-1001',
            'notes' => 'Replay-safe create',
            'idempotency_key' => 'default-key',
            'journal_entry_id' => null,
        ], $overrides);
    }

    private function makePayment(?int $id = null, string $paymentNumber = 'PAY-00001', ?string $idempotencyKey = null): Payment
    {
        return new Payment(
            tenantId: 1,
            paymentNumber: $paymentNumber,
            direction: 'outbound',
            partyType: 'supplier',
            partyId: 15,
            paymentMethodId: 2,
            accountId: 5,
            amount: 120.5,
            currencyId: 1,
            paymentDate: new \DateTimeImmutable('2026-04-28'),
            exchangeRate: 1.0,
            baseAmount: 120.5,
            status: 'draft',
            reference: 'INV-1001',
            notes: 'Replay-safe create',
            idempotencyKey: $idempotencyKey,
            journalEntryId: null,
            id: $id,
        );
    }

    private function makeIdempotencyQueryException(): QueryException
    {
        return new QueryException(
            'sqlite',
            'insert into "payments" (...) values (...)',
            [],
            new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: payments.tenant_id, payments.idempotency_key')
        );
    }
}
