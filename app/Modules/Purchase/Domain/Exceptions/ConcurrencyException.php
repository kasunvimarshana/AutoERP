<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Exceptions;

use RuntimeException;

final class ConcurrencyException extends RuntimeException
{
}
