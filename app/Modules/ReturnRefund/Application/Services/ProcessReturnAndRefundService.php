<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\ReturnRefund\Application\Contracts\ProcessReturnAndRefundServiceInterface;
use Modules\ReturnRefund\Application\DTOs\ProcessReturnInput;
use Modules\ReturnRefund\Application\DTOs\ProcessReturnResult;
use Modules\ReturnRefund\Domain\Entities\RefundTransaction;
use Modules\ReturnRefund\Domain\Entities\ReturnInspection;
use Modules\ReturnRefund\Domain\Events\ReturnRefundDrafted;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\RefundTransactionRepositoryInterface;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\ReturnInspectionRepositoryInterface;

class ProcessReturnAndRefundService implements ProcessReturnAndRefundServiceInterface
{
    public function __construct(
        private readonly ReturnInspectionRepositoryInterface $returnInspectionRepository,
        private readonly RefundTransactionRepositoryInterface $refundTransactionRepository,
    ) {
    }

    public function execute(ProcessReturnInput $input): ProcessReturnResult
    {
        return DB::transaction(function () use ($input): ProcessReturnResult {
            $inspection = new ReturnInspection(
                id: (string) Str::uuid(),
                tenantId: $input->tenantId,
                rentalTransactionId: $input->rentalTransactionId,
                isDamaged: $input->isDamaged,
                damageNotes: $input->damageNotes,
                damageCharge: $input->damageCharge,
                fuelAdjustmentCharge: $input->fuelAdjustmentCharge,
                lateReturnCharge: $input->lateReturnCharge,
                inspectedAt: new \DateTime(),
            );

            $this->returnInspectionRepository->create($inspection);

            $adjustmentAmount = $inspection->totalAdjustments();
            $netRefundAmount = bcsub($input->grossAmount, $adjustmentAmount, 6);
            if (bccomp($netRefundAmount, '0', 6) < 0) {
                $netRefundAmount = '0.000000';
            }

            $refund = new RefundTransaction(
                id: (string) Str::uuid(),
                tenantId: $input->tenantId,
                rentalTransactionId: $input->rentalTransactionId,
                refundNumber: $this->buildRefundNumber($input->tenantId),
                grossAmount: $input->grossAmount,
                adjustmentAmount: $adjustmentAmount,
                netRefundAmount: $netRefundAmount,
                status: 'draft',
                financeReferenceId: null,
                createdAt: new \DateTime(),
            );

            $this->refundTransactionRepository->create($refund);

            Event::dispatch(new ReturnRefundDrafted(
                tenantId: $input->tenantId,
                rentalTransactionId: $input->rentalTransactionId,
                refundId: $refund->getId(),
                refundNumber: $refund->getRefundNumber(),
                grossAmount: $refund->getGrossAmount(),
                adjustmentAmount: $refund->getAdjustmentAmount(),
                netRefundAmount: $refund->getNetRefundAmount(),
            ));

            return new ProcessReturnResult(
                inspectionId: $inspection->getId(),
                refundId: $refund->getId(),
                refundNumber: $refund->getRefundNumber(),
                grossAmount: $refund->getGrossAmount(),
                adjustmentAmount: $refund->getAdjustmentAmount(),
                netRefundAmount: $refund->getNetRefundAmount(),
                status: $refund->getStatus(),
            );
        });
    }

    private function buildRefundNumber(string $tenantId): string
    {
        return sprintf('RFD-%s-%s', strtoupper(substr($tenantId, 0, 6)), (new \DateTimeImmutable())->format('YmdHisv'));
    }
}
