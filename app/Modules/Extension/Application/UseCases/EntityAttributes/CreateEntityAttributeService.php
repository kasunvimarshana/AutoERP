<?php

declare(strict_types=1);

namespace Modules\Extension\Application\UseCases\EntityAttributes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Extension\Application\Repositories\EntityAttributeRepositoryInterface;
use Modules\Extension\Domain\Constants\ExtensionErrorCode;
use Throwable;

final class CreateEntityAttributeService
{
    public function __construct(private readonly EntityAttributeRepositoryInterface $repository) {}

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ExtensionErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
