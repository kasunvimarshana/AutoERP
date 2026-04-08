<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\ValueObjectGenerator;

/**
 * MakeValueObjectCommand
 *
 * Usage:
 *   php artisan ddd:make:value-object Ordering Money
 *   php artisan ddd:make:value-object Ordering OrderStatus --force
 */
final class MakeValueObjectCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:value-object
        {context : Bounded context name (PascalCase)}
        {name    : Value Object class name (PascalCase)}
        {--force : Overwrite existing file}';

    protected $description = 'Generate a Domain Value Object class';

    protected function generators(): array
    {
        return [app(ValueObjectGenerator::class)];
    }
}
