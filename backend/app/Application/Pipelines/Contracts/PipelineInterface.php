<?php

declare(strict_types=1);

namespace App\Application\Pipelines\Contracts;

/**
 * Contract for a processing pipeline.
 */
interface PipelineInterface
{
    /**
     * Send a payload through all registered pipeline stages.
     *
     * @param  mixed $payload  The object / array being processed.
     * @return mixed           The processed payload.
     */
    public function process(mixed $payload): mixed;
}
