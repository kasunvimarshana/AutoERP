<?php

declare(strict_types=1);

namespace Modules\Sales\Application\UseCases\GdnHeaders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\UseCases\GdnHeaders\DeleteGdnHeaderServiceInterface;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class DeleteGdnHeaderService implements DeleteGdnHeaderServiceInterface
{
    public function __construct(private readonly GdnHeaderRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'GdnHeader not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}