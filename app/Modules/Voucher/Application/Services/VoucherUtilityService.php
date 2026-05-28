<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Services;

use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\Services\VoucherUtilityServiceInterface;
use Modules\Voucher\Application\Repositories\VoucherLineRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;

final class VoucherUtilityService implements VoucherUtilityServiceInterface
{
    public function __construct(
        private readonly VoucherRepositoryInterface $voucherRepository,
        private readonly VoucherLineRepositoryInterface $voucherLineRepository,
    ) {}

    public function previewNumber(int $tenantId, ?int $organizationUnitId, string $typeCode): Result
    {
        return Result::success(['voucher_number' => strtoupper(substr($typeCode, 0, 4)) . '-' . $tenantId . '-' . now()->format('YmdHis')]);
    }

    public function validateBalance(array $lines): Result
    {
        $debit = 0.0; $credit = 0.0;
        foreach ($lines as $line) {
            if (!is_array($line)) { continue; }
            $debit += (float)($line['debit_amount'] ?? 0);
            $credit += (float)($line['credit_amount'] ?? 0);
        }

        return Result::success(['is_balanced' => abs($debit - $credit) <= 0.0001, 'total_debit' => round($debit, 4), 'total_credit' => round($credit, 4)]);
    }

    public function validatePaymentMethod(int $tenantId, int $paymentMethodId): Result
    {
        return Result::success(['tenant_id' => $tenantId, 'payment_method_id' => $paymentMethodId, 'is_valid' => $paymentMethodId > 0]);
    }

    public function previewPosting(int $voucherId): Result
    {
        return Result::success(['voucher' => $this->voucherRepository->findById($voucherId), 'lines' => $this->voucherLineRepository->list(['voucher_id' => $voucherId])]);
    }
}