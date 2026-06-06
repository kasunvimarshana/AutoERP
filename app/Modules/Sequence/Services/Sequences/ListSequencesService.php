<?php

declare(strict_types=1);

namespace Modules\Sequence\Services\Sequences;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Sequence\Constants\SequenceErrorCode;
use Modules\Sequence\Repositories\SequenceRepositoryInterface;
use Throwable;

final class ListSequencesService
{
    public function __construct(private readonly SequenceRepositoryInterface $sequences) {}

    public function execute(array $filters): Result
    {
        try {
            $result = $this->sequences->pageByFilters(
                isset($filters['tenant_id']) ? (int) $filters['tenant_id'] : null,
                isset($filters['organization_unit_id']) ? (int) $filters['organization_unit_id'] : null,
                isset($filters['document_type']) ? trim((string) $filters['document_type']) : null,
                isset($filters['period_type']) ? trim((string) $filters['period_type']) : null,
                isset($filters['period_value']) ? trim((string) $filters['period_value']) : null,
                max(
                    1,
                    (int) ($filters['per_page'] ?? (int) config('sequence.pagination.default_per_page', 20)),
                ),
                max(1, (int) ($filters['page'] ?? 1)),
            );

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
