<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;

final class ExpenseNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function next(int $tenantId, int $organizationUnitId, string $expenseDate): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'document_type' => 'expense',
            'period_type' => 'yearly',
            'at_date' => $expenseDate,
            'prefix' => 'EXP-{PERIOD}-',
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }
}
