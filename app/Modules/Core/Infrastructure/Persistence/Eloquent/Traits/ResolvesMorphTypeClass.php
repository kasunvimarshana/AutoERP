<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;

trait ResolvesMorphTypeClass
{
    protected function resolveMorphTypeClass(?string $type): ?string
    {
        if ($type === null || $type === '') {
            return null;
        }

        $resolved = Relation::getMorphedModel($type);

        return is_string($resolved) ? $resolved : $type;
    }
}
