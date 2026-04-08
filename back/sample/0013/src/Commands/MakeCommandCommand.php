<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\CommandGenerator;
use Archify\DddArchitect\Generators\CommandHandlerGenerator;

/**
 * MakeCommandCommand — Generates a CQRS Command + Handler pair.
 *
 * Usage:
 *   php artisan ddd:make:command Ordering CreateOrder
 *   php artisan ddd:make:command Billing ProcessPayment --force
 *
 * Creates:
 *   Application/Commands/CreateOrderCommand.php
 *   Application/Handlers/CreateOrderHandler.php
 */
final class MakeCommandCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:command
        {context : Bounded context name (PascalCase)}
        {name    : Command name without the "Command" suffix (e.g. CreateOrder)}
        {--force : Overwrite existing files}';

    protected $description = 'Generate a CQRS Command and its Handler';

    protected function generators(): array
    {
        return [
            app(CommandGenerator::class),
            app(CommandHandlerGenerator::class),
        ];
    }
}
