<?php

declare(strict_types=1);

namespace Modules\Configuration\Console\Commands;

use Illuminate\Console\Command;
use Modules\Configuration\Services\ClearConfigurationCacheService;

final class ConfigClearCacheCommand extends Command
{
    protected $signature = 'config:clear-cache';

    protected $description = 'Clear configuration module cache';

    public function __construct(private readonly ClearConfigurationCacheService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->service->execute();

        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $this->info('Configuration module cache cleared.');

        return self::SUCCESS;
    }
}
