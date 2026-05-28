<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Payment;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Application\Services\AdvancePaymentAllocationService;
use Modules\Payment\Application\Services\PaymentAllocationService;
use Modules\Payment\Application\Services\PaymentService;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use PHPUnit\Framework\TestCase;

final class PaymentCoreServiceTest extends TestCase
{
    public function testCreatePaymentIsIdempotentByTenantAndKey(): void
    {
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);

        $paymentMethodRepository
            ->method('findById')
            ->with(9)
            ->willReturn(new DataRecord([
                'id' => 9,
                'tenant_id' => 1,
                'is_active' => true,
            ]));

        $paymentRepository
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'idempotency_key' => 'abc-1',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 77,
                    'tenant_id' => 1,
                    'idempotency_key' => 'abc-1',
                    'status' => 'draft',
                ]),
            ]);

        $paymentRepository->expects(self::never())->method('create');

        $service = new PaymentService($paymentRepository, $paymentMethodRepository);
        $result = $service->createPayment([
            'tenant_id' => 1,
            'payment_number' => 'PMT-001',
            'payment_date' => '2026-05-28',
            'amount' => 100,
            'direction' => 'inbound',
            'payment_method_id' => 9,
            'account_id' => 3,
            'idempotency_key' => 'abc-1',
        ]);

        self::assertTrue($result->isSuccess());
        self::assertSame(77, $result->valueOrFail()->id());
    }

    public function testUpdatePaymentRejectsStructuralMutationForPostedPayment(): void
    {
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);

        $paymentRepository
            ->method('findById')
            ->with(10)
            ->willReturn(new DataRecord([
                'id' => 10,
                'tenant_id' => 1,
                'payment_method_id' => 9,
                'payment_number' => 'PMT-010',
                'payment_date' => '2026-05-28',
                'amount' => 100,
                'account_id' => 3,
                'exchange_rate' => 1,
                'direction' => 'inbound',
                'status' => 'posted',
            ]));

        $paymentRepository->expects(self::never())->method('update');

        $service = new PaymentService($paymentRepository, $paymentMethodRepository);
        $result = $service->updatePayment(10, ['amount' => 120]);

        self::assertTrue($result->isFailure());
        self::assertSame(PaymentErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testCreatePaymentAllocationRejectsOverAllocation(): void
    {
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $allocationRepository = $this->createMock(PaymentAllocationRepositoryInterface::class);

        $paymentRepository
            ->method('findById')
            ->with(11)
            ->willReturn(new DataRecord([
                'id' => 11,
                'tenant_id' => 1,
                'organization_unit_id' => null,
                'row_version' => 2,
                'amount' => 100,
                'status' => 'posted',
            ]));

        $allocationRepository
            ->method('list')
            ->willReturnCallback(static function (array $criteria): array {
                if (isset($criteria['payment_id'], $criteria['document_type'], $criteria['document_id'])) {
                    return [];
                }

                return [
                    new DataRecord([
                        'id' => 1,
                        'payment_id' => 11,
                        'allocated_amount' => 90,
                    ]),
                ];
            });

        $paymentRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $allocationRepository->expects(self::never())->method('create');

        $service = new PaymentAllocationService($allocationRepository, $paymentRepository);
        $result = $service->createAllocation([
            'tenant_id' => 1,
            'payment_id' => 11,
            'document_type' => 'sales_invoice',
            'document_id' => 501,
            'allocated_amount' => 20,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT, $result->errorOrFail()->code);
    }

    public function testCreateAdvanceAllocationUpdatesRemainingAndStatus(): void
    {
        $advanceRepository = $this->createMock(AdvancePaymentRepositoryInterface::class);
        $allocationRepository = $this->createMock(AdvancePaymentAllocationRepositoryInterface::class);

        $advanceRepository
            ->method('findById')
            ->with(44)
            ->willReturn(new DataRecord([
                'id' => 44,
                'tenant_id' => 1,
                'organization_unit_id' => null,
                'row_version' => 1,
                'amount' => 100,
                'remaining_amount' => 60,
                'status' => 'open',
            ]));

        $advanceRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $allocationRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn (array $attributes): bool =>
                $attributes['advance_payment_id'] === 44
                && $attributes['allocated_amount'] === 20.0))
            ->willReturn(new DataRecord([
                'id' => 6,
                'advance_payment_id' => 44,
                'allocated_amount' => 20,
            ]));

        $advanceRepository
            ->expects(self::once())
            ->method('update')
            ->with(44, [
                'remaining_amount' => 40.0,
                'status' => 'partially_applied',
                'row_version' => 2,
            ])
            ->willReturn(new DataRecord([
                'id' => 44,
                'remaining_amount' => 40,
                'status' => 'partially_applied',
            ]));

        $service = new AdvancePaymentAllocationService($allocationRepository, $advanceRepository);
        $result = $service->createAllocation([
            'tenant_id' => 1,
            'advance_payment_id' => 44,
            'document_type' => 'purchase_invoice',
            'document_id' => 3001,
            'allocated_amount' => 20,
        ]);

        self::assertTrue($result->isSuccess());
        self::assertSame(6, $result->valueOrFail()->id());
    }
}
