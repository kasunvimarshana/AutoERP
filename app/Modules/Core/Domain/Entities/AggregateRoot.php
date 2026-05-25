<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Entities;

use Modules\Core\Domain\Events\RecordsDomainEvents;

abstract class AggregateRoot extends Entity
{
    use RecordsDomainEvents;
}
