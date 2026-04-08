<?php

namespace App\Modules\ModuleGenerator\Registry;

use App\Modules\ModuleGenerator\Contracts\GeneratorInterface;

class GeneratorRegistry
{
    protected array $generators = [];

    public function register(GeneratorInterface $generator): void
    {
        $this->generators[$generator->key()] = $generator;
    }

    public function get(string $key): GeneratorInterface
    {
        return $this->generators[$key];
    }
}
