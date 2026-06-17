<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use RuntimeException;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;

final class PurchaseNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function next(int $tenantId, string $prefix, string $table, string $column): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $tenantId,
            'document_type' => $table.'.'.$column,
            'prefix' => $prefix.'-',
            'padding' => 6,
            'period_type' => 'infinite',
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        $payload = $result->valueOrFail();

        return (string) $payload['generated_number'];
    }
}
