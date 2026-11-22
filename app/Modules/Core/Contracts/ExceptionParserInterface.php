<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

use Throwable;

interface ExceptionParserInterface
{
    /**
     * @return array{code:string, message:string, context:array<string, scalar|array|null>}
     */
    public function parse(Throwable $exception): array;
}
