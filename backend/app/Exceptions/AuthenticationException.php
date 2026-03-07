<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when authentication credentials are invalid or the account is inactive.
 */
class AuthenticationException extends RuntimeException {}
