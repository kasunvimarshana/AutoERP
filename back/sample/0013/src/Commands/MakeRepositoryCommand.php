<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\EloquentRepositoryGenerator;
use Archify\DddArchitect\Generators\RepositoryInterfaceGenerator;

/**
 * MakeRepositoryCommand — Generates a Repository Interface + Eloquent Implementation pair.
 *
 * Usage:
 *   php artisan ddd:make:repository Ordering Order
 *   php artisan ddd:make:repository Billing Invoice --force
 *
 * Creates:
 *   Domain/Repositories/OrderRepositoryInterface.php
 *   Infrastructure/Persistence/Repositories/EloquentOrderRepository.php
 */
final class MakeRepositoryCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:repository
        {context : Bounded context name (PascalCase)}
        {name    : Entity/Aggregate name the repository manages (PascalCase)}
        {--force : Overwrite existing files}';

    protected $description = 'Generate a Repository Interface and its Eloquent implementation';

    protected function generators(): array
    {
        return [
            app(RepositoryInterfaceGenerator::class),
            app(EloquentRepositoryGenerator::class),
        ];
    }
}
