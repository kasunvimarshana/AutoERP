<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;

final class PaymentNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function resolve(CreatePaymentData $data): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'document_type' => $data->direction->value === 'inbound'
                ? 'receipt_voucher'
                : 'payment_voucher',
            'period_type' => 'yearly',
            'at_date' => $data->paymentDate,
            'prefix' => $data->direction->value === 'inbound'
                ? 'RV-{PERIOD}-'
                : 'PV-{PERIOD}-',
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }
}
