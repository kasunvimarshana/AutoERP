<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\AggregateRootGenerator;

/**
 * MakeAggregateCommand
 *
 * Usage:
 *   php artisan ddd:make:aggregate Ordering Order
 *   php artisan ddd:make:aggregate Billing Invoice --force
 */
final class MakeAggregateCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:aggregate
        {context : Bounded context name (PascalCase)}
        {name    : Aggregate Root class name (PascalCase)}
        {--force : Overwrite existing file}';

    protected $description = 'Generate an Aggregate Root class';

    protected function generators(): array
    {
        return [app(AggregateRootGenerator::class)];
    }
}
