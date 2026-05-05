<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\Exceptions;

use RuntimeException;

class InvalidServiceJobStatusTransitionException extends RuntimeException
{
}
