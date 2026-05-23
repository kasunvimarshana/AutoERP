<?php

declare(strict_types=1);

namespace App\Support\Eloquent\Concerns;

use Illuminate\Support\Str;

trait UsesUuidRouteKey
{
    protected static function bootUsesUuidRouteKey(): void
    {
        static::creating(function (self $model): void {
            if ($model->getAttribute('uuid') === null) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
