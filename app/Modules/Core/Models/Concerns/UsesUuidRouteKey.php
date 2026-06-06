<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use Modules\Core\Constants\SchemaColumns;

trait UsesUuidRouteKey
{
    public function getRouteKeyName(): string
    {
        return SchemaColumns::UUID;
    }
}
