<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Sequence\Services\Sequences;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Sequence\Constants\SequenceErrorCode;
use Modules\Sequence\Repositories\SequenceRepositoryInterface;
use Throwable;

final class GetSequenceService
{
    public function __construct(private readonly SequenceRepositoryInterface $sequences) {}

    public function execute(int|string $id, ?int $tenantId = null): Result
    {
        try {
            $record = $tenantId !== null
                ? $this->sequences->findByIdInTenant($id, $tenantId)
                : $this->sequences->findById($id);

            if ($record === null) {
                return Result::failure(new Error(SequenceErrorCode::NOT_FOUND, 'Sequence not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
