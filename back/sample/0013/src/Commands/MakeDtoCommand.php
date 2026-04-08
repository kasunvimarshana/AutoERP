<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\DtoGenerator;

/**
 * MakeDtoCommand
 *
 * Usage:
 *   php artisan ddd:make:dto Ordering CreateOrder
 *   php artisan ddd:make:dto Catalog ProductDetail --force
 */
final class MakeDtoCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:dto
        {context : Bounded context name (PascalCase)}
        {name    : DTO name without the "Dto" suffix (e.g. CreateOrder)}
        {--force : Overwrite existing file}';

    protected $description = 'Generate a Data Transfer Object (DTO) class';

    protected function generators(): array
    {
        return [app(DtoGenerator::class)];
    }
}
