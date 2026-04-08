<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Generator\Core\GeneratorEngine;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Enterprise Module Generator V4';

    public function handle(GeneratorEngine $engine)
    {
        $engine->run([
            'name' => $this->argument('name'),
            'basePath' => base_path('modules/'.$this->argument('name'))
        ]);

        $this->info('Module generated successfully.');
    }
}
