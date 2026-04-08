<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\ModuleGenerator\Services\ModuleGeneratorService;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Enterprise module generator';

    public function handle(ModuleGeneratorService $service)
    {
        $service->generate([
            'name' => $this->argument('name'),
            'path' => base_path('modules/'.$this->argument('name')),
        ]);

        $this->info('Module generated successfully.');
    }
}
