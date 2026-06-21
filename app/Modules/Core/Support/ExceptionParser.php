<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Modules\Core\Contracts\ExceptionParserInterface;
use Modules\Core\Exceptions\DomainException;
use Throwable;

final class ExceptionParser implements ExceptionParserInterface
{
    public function __construct(private readonly ConfigRepository $config) {}

    public function parse(Throwable $exception): array
    {
        $safeToExpose = $exception instanceof DomainException || $exception instanceof InvalidArgumentException;
        $debug = (bool) $this->config->get('app.debug', false);
        $message = $safeToExpose || $debug
            ? trim($exception->getMessage())
            : 'Unexpected error.';
        $message = $message !== '' ? $message : 'Unexpected error.';

        $numericCode = $exception->getCode();
        $code = is_int($numericCode) && $numericCode > 0
            ? 'EXCEPTION_'.(string) $numericCode
            : 'EXCEPTION';

        return [
            'code' => $code,
            'message' => $message,
            'context' => $debug ? ['exception' => $exception::class] : [],
        ];
    }
}
