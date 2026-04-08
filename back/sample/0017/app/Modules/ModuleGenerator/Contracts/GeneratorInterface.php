<?php

namespace App\Modules\ModuleGenerator\Contracts;

interface GeneratorInterface
{
    public function key(): string;
    public function generate(array $context): void;
}
