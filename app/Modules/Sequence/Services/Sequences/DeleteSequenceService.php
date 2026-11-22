<?php

declare(strict_types=1);

namespace Modules\Sequence\Services\Sequences;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Sequence\Constants\SequenceErrorCode;
use Modules\Sequence\Repositories\SequenceRepositoryInterface;
use Throwable;

final class DeleteSequenceService
{
    public function __construct(private readonly SequenceRepositoryInterface $sequences) {}

    public function execute(int|string $id, ?int $tenantId = null): Result
    {
        try {
            $existing = $tenantId !== null
                ? $this->sequences->findByIdInTenant($id, $tenantId)
                : $this->sequences->findById($id);

            if ($existing === null) {
                return Result::failure(new Error(SequenceErrorCode::NOT_FOUND, 'Sequence not found.'));
            }

            if ((int) $existing->get('next_number', 1) > 1) {
                return Result::failure(new Error(
                    SequenceErrorCode::CONFLICT,
                    'Sequences that have already generated document numbers cannot be deleted.',
                ));
            }

            return Result::success($this->sequences->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
