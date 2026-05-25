<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\SalaryStructures;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\CreateSalaryStructureServiceInterface;
use Modules\HR\Application\Repositories\SalaryStructureRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class CreateSalaryStructureService implements CreateSalaryStructureServiceInterface
{
    public function __construct(private readonly SalaryStructureRepositoryInterface $repository)
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