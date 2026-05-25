<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Concerns;

use Modules\Core\Infrastructure\Persistence\Eloquent\Constants\SchemaColumns;

trait UsesUuidRouteKey
{
    public function getRouteKeyName(): string
    {
        return SchemaColumns::UUID;
    }
}
