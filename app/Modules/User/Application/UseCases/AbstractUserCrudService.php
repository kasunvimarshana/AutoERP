<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\User\Domain\Constants\UserErrorCode;
use Throwable;

abstract class AbstractUserCrudService
{
    protected function success(mixed $value): Result
    {
        return Result::success($value);
    }

    protected function failure(string $code, string $message, array $context = []): Result
    {
        return Result::failure(new Error($code, $message, $context));
    }

    protected function notFound(string $message = 'Record not found.'): Result
    {
        return $this->failure(UserErrorCode::NOT_FOUND, $message);
    }

    protected function fromThrowable(Throwable $exception): Result
    {
        return $this->failure(UserErrorCode::INVALID_VALUE, $exception->getMessage());
    }

    protected function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $validated = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $validated === false ? null : (int) $validated;
    }

    protected function toBool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
