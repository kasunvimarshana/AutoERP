<?php

declare(strict_types=1);

namespace Modules\Core\Application\Handlers;

use Closure;

/**
 * Base pipeline handler (pipe stage).
 *
 * Each handler performs a single, well-defined step in a processing pipeline.
 * Use Laravel's Pipeline to compose multiple handlers in sequence.
 *
 * @template TPayload
 */
abstract class PipelineHandler
{
    /**
     * Process the payload and pass it to the next handler in the pipeline.
     *
     * @param  TPayload  $payload
     * @param  Closure(TPayload): TPayload  $next
     * @return TPayload
     */
    abstract public function handle(mixed $payload, Closure $next): mixed;
}
