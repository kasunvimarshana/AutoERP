<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Commands;

use Archify\DddArchitect\Generators\DomainEventGenerator;

/**
 * MakeEventCommand
 *
 * Usage:
 *   php artisan ddd:make:event Ordering OrderWasPlaced
 *   php artisan ddd:make:event Billing InvoiceWasPaid --force
 */
final class MakeEventCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:event
        {context : Bounded context name (PascalCase)}
        {name    : Domain event class name — use past tense (e.g. OrderWasPlaced)}
        {--force : Overwrite existing file}';

    protected $description = 'Generate a Domain Event class';

    protected function generators(): array
    {
        return [app(DomainEventGenerator::class)];
    }
}
