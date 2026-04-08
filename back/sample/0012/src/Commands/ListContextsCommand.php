<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

class ListContextsCommand extends BaseCommand
{
    protected $signature = 'ddd:list';
    protected $description = 'List all discovered DDD bounded contexts in this application';

    public function handle(): int
    {
        $contexts = $this->registrar()->all();

        if (empty($contexts)) {
            $this->components->warn(
                'No bounded contexts found. Run <options=bold>php artisan ddd:make-context {name}</> to create one.'
            );
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('  <options=bold>Discovered Bounded Contexts</>');
        $this->newLine();

        $rows = array_map(function (string $context) {
            $path = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->registrar()->path($context));
            return [
                "<fg=green>{$context}</>",
                $this->registrar()->namespace($context),
                $path,
            ];
        }, $contexts);

        $this->table(['Context', 'Namespace', 'Path'], $rows);

        $this->newLine();
        $this->line('  Total: <options=bold>' . count($contexts) . '</> context(s)');
        $this->newLine();

        return self::SUCCESS;
    }
}
