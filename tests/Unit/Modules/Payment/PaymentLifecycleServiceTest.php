<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Payment;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\WriteOffRepositoryInterface;
use Modules\Payment\Application\Services\PaymentPostingService;
use Modules\Payment\Application\Services\PaymentReversalService;
use Modules\Payment\Application\Services\RefundService;
use Modules\Payment\Application\Services\WriteOffService;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use PHPUnit\Framework\TestCase;

final class PaymentLifecycleServiceTest extends TestCase
{
    public function testPostPaymentTransitionsDraftToPosted(): void
    {
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository
            ->method('findById')
            ->with(5)
            ->willReturn(new DataRecord([
                'id' => 5,
                'tenant_id' => 1,
                'status' => PaymentStatus::DRAFT,
                'row_version' => 2,
            ]));

        $paymentRepository
            ->expects(self::once())
            ->method('update')
            ->with(5, self::arrayHasKey('posted_at'))
            ->willReturn(new DataRecord([
                'id' => 5,
                'status' => PaymentStatus::POSTED,
            ]));

        $service = new PaymentPostingService($paymentRepository);
        $result = $service->postPayment(5, ['posted_by' => 9]);

        self::assertTrue($result->isSuccess());
    }

    public function testReversePaymentCreatesReversalAndMarksSourceReversed(): void
    {
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository
            ->method('findById')
            ->willReturn(new DataRecord([
                'id' => 5,
                'tenant_id' => 1,
                'organization_unit_id' => null,
                'payment_number' => 'PMT-5',
                'status' => PaymentStatus::POSTED,
                'row_version' => 3,
                'party_type' => 'customer',
                'party_id' => 10,
                'amount' => 100,
                'direction' => 'inbound',
                'payment_group_id' => null,
                'payment_method_id' => 2,
                'account_id' => 7,
                'currency_id' => null,
                'exchange_rate' => 1,
                'base_amount' => 100,
            ]));

        $paymentRepository
            ->method('list')
            ->willReturn([]);

        $paymentRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $paymentRepository
            ->expects(self::once())
            ->method('create')
            ->willReturn(new DataRecord(['id' => 50, 'payment_number' => 'PMT-5-R']));

        $paymentRepository
            ->expects(self::once())
            ->method('update')
            ->willReturn(new DataRecord(['id' => 5, 'status' => 'reversed']));

        $service = new PaymentReversalService($paymentRepository);
        $result = $service->reversePayment(5, ['payment_number' => 'PMT-5-R']);

        self::assertTrue($result->isSuccess());
    }

    public function testRefundPaymentCreatesOutboundRefund(): void
    {
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository
            ->method('findById')
            ->with(5)
            ->willReturn(new DataRecord([
                'id' => 5,
                'tenant_id' => 1,
                'organization_unit_id' => null,
                'payment_number' => 'PMT-5',
                'status' => PaymentStatus::POSTED,
                'row_version' => 3,
                'party_type' => 'customer',
                'party_id' => 10,
                'amount' => 100,
                'direction' => 'inbound',
                'payment_group_id' => null,
                'payment_method_id' => 2,
                'account_id' => 7,
                'currency_id' => null,
                'exchange_rate' => 1,
                'base_amount' => 100,
            ]));

        $paymentRepository
            ->expects(self::once())
            ->method('create')
            ->willReturn(new DataRecord(['id' => 60, 'payment_number' => 'RFD-1']));

        $service = new RefundService($paymentRepository);
        $result = $service->refundPayment(5, ['payment_number' => 'RFD-1', 'amount' => 25]);

        self::assertTrue($result->isSuccess());
    }

    public function testWriteOffRejectsStructuralMutationAfterPosting(): void
    {
        $writeOffRepository = $this->createMock(WriteOffRepositoryInterface::class);
        $writeOffRepository
            ->method('findById')
            ->with(9)
            ->willReturn(new DataRecord([
                'id' => 9,
                'status' => 'posted',
                'document_type' => 'sales_invoice',
                'document_id' => 1,
                'amount' => 10,
            ]));

        $writeOffRepository->expects(self::never())->method('update');

        $service = new WriteOffService($writeOffRepository);
        $result = $service->updateWriteOff(9, ['amount' => 20]);

        self::assertTrue($result->isFailure());
        self::assertSame(PaymentErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }
}
