<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\PerformanceReviews;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\Repositories\PerformanceReviewRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class CreatePerformanceReviewService implements CreatePerformanceReviewServiceInterface
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}