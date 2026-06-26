<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserErrorCode;
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
        if ($exception instanceof AuthorizationException) {
            return $this->failure(UserErrorCode::FORBIDDEN, $exception->getMessage());
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->failure(UserErrorCode::NOT_FOUND, 'The requested record was not found.');
        }

        if ($exception instanceof QueryException) {
            report($exception);

            return $this->failure(
                UserErrorCode::CONFLICT,
                'The requested change conflicts with existing user data. Refresh and try again.',
            );
        }

        $message = trim($exception->getMessage());
        $lowerMessage = mb_strtolower($message);
        if (str_contains($lowerMessage, 'changed after it was loaded')) {
            return $this->failure(UserErrorCode::STALE_RECORD, $message);
        }
        if (str_contains($lowerMessage, 'not found')) {
            return $this->failure(UserErrorCode::NOT_FOUND, $message);
        }
        if (str_contains($lowerMessage, 'already exists') || str_contains($lowerMessage, 'cannot be') || str_contains($lowerMessage, 'before archiving')) {
            return $this->failure(UserErrorCode::CONFLICT, $message);
        }

        return $this->failure(UserErrorCode::INVALID_VALUE, $message !== '' ? $message : 'The request could not be completed.');
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
