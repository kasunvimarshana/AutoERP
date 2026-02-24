<?php

namespace Modules\Communication\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Communication\Domain\Contracts\ChannelRepositoryInterface;
use Modules\Communication\Domain\Contracts\MessageRepositoryInterface;
use Modules\Communication\Infrastructure\Repositories\ChannelRepository;
use Modules\Communication\Infrastructure\Repositories\MessageRepository;

class CommunicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChannelRepositoryInterface::class, ChannelRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'communication');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
