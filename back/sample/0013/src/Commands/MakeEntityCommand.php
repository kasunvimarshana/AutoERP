<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\EntityGenerator;

/**
 * MakeEntityCommand
 *
 * Usage:
 *   php artisan ddd:make:entity Ordering Order
 *   php artisan ddd:make:entity Ordering Order --force
 */
final class MakeEntityCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:entity
        {context : Bounded context name (PascalCase)}
        {name    : Entity class name (PascalCase)}
        {--force : Overwrite existing file}';

    protected $description = 'Generate a Domain Entity class';

    protected function generators(): array
    {
        return [app(EntityGenerator::class)];
    }
}
