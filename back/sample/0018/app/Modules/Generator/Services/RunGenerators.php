<?php

namespace App\Modules\Generator\Services;

class RunGenerators
{
    public function handle(array $context): void
    {
        $generators = [
            \App\Modules\Generator\Generators\DomainGenerator::class,
            \App\Modules\Generator\Generators\ApplicationGenerator::class,
        ];

        foreach ($generators as $generator) {
            (new $generator)->generate($context);
        }
    }
}
