<?php

declare(strict_types=1);

namespace Modules\Core\Application\Pipelines;

final class Pipeline
{
    /**
     * @param list<PipelineStageInterface> $stages
     */
    public function __construct(private readonly array $stages)
    {
    }

    public function process(mixed $payload): mixed
    {
        $next = static fn (mixed $carry): mixed => $carry;

        foreach (array_reverse($this->stages) as $stage) {
            $currentNext = $next;
            $next = static fn (mixed $carry): mixed => $stage->handle($carry, $currentNext);
        }

        return $next($payload);
    }
}
