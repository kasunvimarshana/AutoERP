<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\PerformanceCycles;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\CreatePerformanceCycleServiceInterface;
use Modules\HR\Application\Repositories\PerformanceCycleRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class CreatePerformanceCycleService implements CreatePerformanceCycleServiceInterface
{
    public function __construct(private readonly PerformanceCycleRepositoryInterface $repository)
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