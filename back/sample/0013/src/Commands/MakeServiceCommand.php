<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\DomainServiceGenerator;

/**
 * MakeServiceCommand
 *
 * Usage:
 *   php artisan ddd:make:service Ordering PricingService
 *   php artisan ddd:make:service Identity UniqueEmailChecker --force
 */
final class MakeServiceCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:service
        {context : Bounded context name (PascalCase)}
        {name    : Domain Service class name (PascalCase)}
        {--force : Overwrite existing file}';

    protected $description = 'Generate a Domain Service class';

    protected function generators(): array
    {
        return [app(DomainServiceGenerator::class)];
    }
}
