<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Configuration\Application\DTOs\ConfigurationValueData;
use Modules\Configuration\Application\UseCases\GetConfigurationService;

final class ConfigGetCommand extends Command
{
    protected $signature = 'config:get {key : Configuration key}';

    protected $description = 'Get a configuration value';

    public function __construct(private readonly GetConfigurationService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->service->execute((string) $this->argument('key'));

        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $data = $result->valueOrFail();
        if (! $data instanceof ConfigurationValueData) {
            $this->error('Invalid get response.');

            return self::FAILURE;
        }

        $this->table(
            ['key', 'value', 'source', 'description', 'updated_at'],
            [[
                $data->key,
                $this->formatValue($data->value),
                $data->source,
                $data->description ?? '',
                $data->updatedAt ?? '',
            ]],
        );

        return self::SUCCESS;
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value);

        return is_string($encoded) ? $encoded : '[unserializable]';
    }
}
