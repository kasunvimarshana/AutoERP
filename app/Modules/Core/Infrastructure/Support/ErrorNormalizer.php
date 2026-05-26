<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Support;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\ExceptionParserInterface;
use Modules\Core\Application\Results\Error;
use Throwable;

final class ErrorNormalizer implements ErrorNormalizerInterface
{
    public function __construct(private readonly ExceptionParserInterface $exceptionParser)
    {
    }

    public function normalize(Throwable $exception, string $fallbackCode, array $context = []): Error
    {
        $parsed = $this->exceptionParser->parse($exception);

        $resolvedCode = trim((string) ($parsed['code'] ?? ''));
        if ($resolvedCode === '' || str_starts_with($resolvedCode, 'EXCEPTION')) {
            $resolvedCode = $fallbackCode;
        }

        $resolvedMessage = trim((string) ($parsed['message'] ?? ''));
        if ($resolvedMessage === '') {
            $resolvedMessage = 'Unexpected error.';
        }

        $parsedContext = $parsed['context'] ?? [];
        $parsedContext = is_array($parsedContext) ? $parsedContext : [];

        /** @var array<string, scalar|array|null> $mergedContext */
        $mergedContext = array_merge($parsedContext, $context);

        return new Error($resolvedCode, $resolvedMessage, $mergedContext);
    }
}
