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
        if ($data->paymentNumber !== null && trim($data->paymentNumber) !== '') {
            return trim($data->paymentNumber);
        }

        $result = $this->sequences->execute([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'document_type' => 'payment_'.$data->paymentType->value,
            'period_type' => 'yearly',
            'at_date' => $data->paymentDate,
            'prefix' => strtoupper($data->paymentType->value).'-{PERIOD}-',
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        $payload = $result->valueOrFail();

        return (string) $payload['generated_number'];
    }
}
