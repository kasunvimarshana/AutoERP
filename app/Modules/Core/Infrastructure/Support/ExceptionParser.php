<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Support;

use Modules\Core\Application\Contracts\ExceptionParserInterface;
use Throwable;

final class ExceptionParser implements ExceptionParserInterface
{
    public function parse(Throwable $exception): array
    {
        $message = trim($exception->getMessage());
        $message = $message !== '' ? $message : 'Unexpected error.';

        $code = is_int($exception->getCode()) && $exception->getCode() > 0
            ? 'EXCEPTION_' . (string) $exception->getCode()
            : 'EXCEPTION';

        return [
            'code' => $code,
            'message' => $message,
            'context' => [
                'exception' => $exception::class,
            ],
        ];
    }
}
