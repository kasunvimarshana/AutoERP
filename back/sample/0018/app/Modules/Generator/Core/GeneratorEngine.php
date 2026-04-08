<?php

namespace App\Modules\Generator\Core;

use App\Modules\Generator\Pipeline\GeneratorPipeline;

class GeneratorEngine
{
    public function __construct(protected GeneratorPipeline $pipeline) {}

    public function run(array $context): void
    {
        $this->pipeline->process($context);
    }
}
