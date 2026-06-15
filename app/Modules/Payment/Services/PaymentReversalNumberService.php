<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\DTOs\PaymentReversalData;
use Modules\Payment\Models\Payment;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;

final class PaymentReversalNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function resolve(PaymentReversalData $data, Payment $payment): string
    {
        $manual = trim($data->reversalNumber);
        if ($manual !== '') {
            return $manual;
        }

        $result = $this->sequences->execute([
            'tenant_id' => (int) $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'document_type' => 'payment_reversal_voucher',
            'period_type' => 'yearly',
            'at_date' => $data->reversalDate,
            'prefix' => 'REV-{PERIOD}-',
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }
}
