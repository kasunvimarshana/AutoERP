<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasVehicleServiceJobRowVersion
{
    protected static function bootHasVehicleServiceJobRowVersion(): void
    {
        static::updating(static function (Model $model): void {
            if (! $model->isDirty('row_version')) {
                $model->row_version = ((int) $model->getOriginal('row_version')) + 1;
            }
        });
    }
}
