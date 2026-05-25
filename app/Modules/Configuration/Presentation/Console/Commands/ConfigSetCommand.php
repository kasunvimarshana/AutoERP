<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationFromCliServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationValueData;

final class ConfigSetCommand extends Command
{
    protected $signature = 'config:set
        {key : Configuration key}
        {value : Configuration value (supports json, boolean, numeric, null)}
        {--source=database : Source label persisted to schema (default: database)}
        {--description= : Optional description}';

    protected $description = 'Set a configuration value';

    public function __construct(private readonly SetConfigurationFromCliServiceInterface $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->service->execute(
            (string) $this->argument('key'),
            (string) $this->argument('value'),
            (string) $this->option('source'),
            $this->option('description') !== null ? (string) $this->option('description') : null,
        );

        if ($result->isFailure()) {
            $this->error($result->error()?->message ?? 'Unable to set configuration.');

            return self::FAILURE;
        }

        $data = $result->value();
        if (! $data instanceof ConfigurationValueData) {
            $this->error('Invalid set response.');

            return self::FAILURE;
        }

        $this->info(sprintf('Configuration "%s" saved from %s source.', $data->key, $data->source));

        return self::SUCCESS;
    }
}
