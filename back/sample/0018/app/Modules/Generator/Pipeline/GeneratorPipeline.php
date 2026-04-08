<?php

namespace App\Modules\Generator\Pipeline;

class GeneratorPipeline
{
    public function process(array $context): void
    {
        $stages = [
            \App\Modules\Generator\Services\PrepareContext::class,
            \App\Modules\Generator\Services\RunGenerators::class,
            \App\Modules\Generator\Services\FinalizeModule::class,
        ];

        foreach ($stages as $stage) {
            (new $stage)->handle($context);
        }
    }
}
