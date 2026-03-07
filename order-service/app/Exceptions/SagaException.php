<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by the SagaOrchestrator when a step fails
 * and compensations have been triggered.
 */
final class SagaException extends RuntimeException {}
