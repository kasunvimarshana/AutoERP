<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by a SagaStep when its forward action fails.
 * Caught by SagaOrchestrator to trigger compensations.
 */
final class SagaStepException extends RuntimeException {}
