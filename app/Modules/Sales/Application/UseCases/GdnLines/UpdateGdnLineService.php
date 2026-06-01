<?php

declare(strict_types=1);

namespace Modules\Sales\Application\UseCases\GdnLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\UseCases\GdnLines\UpdateGdnLineServiceInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class UpdateGdnLineService implements UpdateGdnLineServiceInterface
{
    public function __construct(private readonly GdnLineRepositoryInterface $repository) {}

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'GdnLine not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
