<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\QueryGenerator;
use Archify\DddArchitect\Generators\QueryHandlerGenerator;

/**
 * MakeQueryCommand — Generates a CQRS Query + Handler pair.
 *
 * Usage:
 *   php artisan ddd:make:query Ordering GetOrder
 *   php artisan ddd:make:query Catalog ListProducts --force
 *
 * Creates:
 *   Application/Queries/GetOrderQuery.php
 *   Application/Handlers/GetOrderQueryHandler.php
 */
final class MakeQueryCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:query
        {context : Bounded context name (PascalCase)}
        {name    : Query name without the "Query" suffix (e.g. GetOrder)}
        {--force : Overwrite existing files}';

    protected $description = 'Generate a CQRS Query and its Handler';

    protected function generators(): array
    {
        return [
            app(QueryGenerator::class),
            app(QueryHandlerGenerator::class),
        ];
    }
}
