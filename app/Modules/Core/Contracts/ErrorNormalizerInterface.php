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

use Modules\Core\Results\Error;
use Throwable;

interface ErrorNormalizerInterface
{
    /**
     * @param  array<string, scalar|array|null>  $context
     */
    public function normalize(Throwable $exception, string $fallbackCode, array $context = []): Error;
}
