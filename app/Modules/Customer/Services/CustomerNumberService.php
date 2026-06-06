<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;

final class CustomerNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function next(int $tenantId): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'document_type' => 'customer',
            'period_type' => 'infinite',
            'prefix' => 'CUS-',
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }
}
