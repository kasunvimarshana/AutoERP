<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Generate module';

    public function handle()
    {
        $this->info('Module generation started...');
    }
}
