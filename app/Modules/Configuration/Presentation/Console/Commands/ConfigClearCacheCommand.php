<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Configuration\Application\Contracts\UseCases\ClearConfigurationCacheServiceInterface;

final class ConfigClearCacheCommand extends Command
{
    protected $signature = 'config:clear-cache';

    protected $description = 'Clear configuration module cache';

    public function __construct(private readonly ClearConfigurationCacheServiceInterface $service)
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
