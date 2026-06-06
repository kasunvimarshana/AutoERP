<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\UseCases\Sequences;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Domain\Constants\SequenceErrorCode;
use Throwable;

final class DeleteSequenceService
{
    public function __construct(private readonly SequenceRepositoryInterface $sequences) {}

    public function execute(int|string $id): Result
    {
        try {
            if ($this->sequences->findById($id) === null) {
                return Result::failure(new Error(SequenceErrorCode::NOT_FOUND, 'Sequence not found.'));
            }

            return Result::success($this->sequences->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
