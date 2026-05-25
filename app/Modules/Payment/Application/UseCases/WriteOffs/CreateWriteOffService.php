<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\WriteOffs;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\CreateWriteOffServiceInterface;
use Modules\Payment\Application\Repositories\WriteOffRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class CreateWriteOffService implements CreateWriteOffServiceInterface
{
    public function __construct(private readonly WriteOffRepositoryInterface $repository)
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
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}