<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;

final class InvoiceNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function resolve(CreateInvoiceData $data): string
    {
        if ($data->invoiceNumber !== null && trim($data->invoiceNumber) !== '') {
            return trim($data->invoiceNumber);
        }

        $result = $this->sequences->execute([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'document_type' => 'invoice_'.$data->invoiceType->value,
            'period_type' => 'yearly',
            'at_date' => $data->invoiceDate,
            'prefix' => strtoupper($data->invoiceType->value).'-{PERIOD}-',
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        $payload = $result->valueOrFail();

        return (string) $payload['generated_number'];
    }
}
