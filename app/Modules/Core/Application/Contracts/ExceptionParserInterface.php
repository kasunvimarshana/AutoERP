<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use Throwable;

interface ExceptionParserInterface
{
    /**
     * @return array{code:string, message:string, context:array<string, scalar|array|null>}
     */
    public function parse(Throwable $exception): array;
}
