<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VoucherUtilityServiceInterface
{
    public function previewNumber(int $tenantId, ?int $organizationUnitId, string $typeCode): Result;
    public function validateBalance(array $lines): Result;
    public function validatePaymentMethod(int $tenantId, int $paymentMethodId): Result;
    public function previewPosting(int $voucherId): Result;
}