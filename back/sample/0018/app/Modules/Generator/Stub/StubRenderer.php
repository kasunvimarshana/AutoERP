<?php

namespace App\Modules\Generator\Stub;

class StubRenderer
{
    public function render(string $stub, array $data): string
    {
        foreach ($data as $key => $value) {
            $stub = str_replace('{{ '.$key.' }}', $value, $stub);
        }

        return $stub;
    }
}
