<?php

declare(strict_types=1);

namespace Modules\Core\Application\Pipelines;

interface PipelineStageInterface
{
    public function handle(mixed $payload, callable $next): mixed;
}
