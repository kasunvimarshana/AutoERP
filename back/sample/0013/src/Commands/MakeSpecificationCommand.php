<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\SpecificationGenerator;

/**
 * MakeSpecificationCommand
 *
 * Usage:
 *   php artisan ddd:make:specification Ordering OrderIsEligibleForDiscount
 *   php artisan ddd:make:specification Identity UserIsActive --force
 */
final class MakeSpecificationCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:specification
        {context : Bounded context name (PascalCase)}
        {name    : Specification class name (PascalCase)}
        {--force : Overwrite existing file}';

    protected $description = 'Generate a Domain Specification class';

    protected function generators(): array
    {
        return [app(SpecificationGenerator::class)];
    }
}
