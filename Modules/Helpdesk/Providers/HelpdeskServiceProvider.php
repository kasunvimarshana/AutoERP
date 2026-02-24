<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Domain\Contracts\KbArticleRepositoryInterface;
use Modules\Helpdesk\Domain\Contracts\KbCategoryRepositoryInterface;
use Modules\Helpdesk\Domain\Contracts\TicketCategoryRepositoryInterface;
use Modules\Helpdesk\Domain\Contracts\TicketRepositoryInterface;
use Modules\Helpdesk\Infrastructure\Repositories\KbArticleRepository;
use Modules\Helpdesk\Infrastructure\Repositories\KbCategoryRepository;
use Modules\Helpdesk\Infrastructure\Repositories\TicketCategoryRepository;
use Modules\Helpdesk\Infrastructure\Repositories\TicketRepository;

class HelpdeskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TicketCategoryRepositoryInterface::class, TicketCategoryRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(KbCategoryRepositoryInterface::class, KbCategoryRepository::class);
        $this->app->bind(KbArticleRepositoryInterface::class, KbArticleRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'helpdesk');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
