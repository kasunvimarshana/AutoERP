<?php

namespace App\Core\Traits;

use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            Log::info('Model created', [
                'model' => get_class($model),
                'id' => $model->id,
                'user_id' => auth()->id(),
            ]);
        });

        static::updated(function ($model) {
            Log::info('Model updated', [
                'model' => get_class($model),
                'id' => $model->id,
                'user_id' => auth()->id(),
                'changes' => $model->getChanges(),
            ]);
        });

        static::deleted(function ($model) {
            Log::info('Model deleted', [
                'model' => get_class($model),
                'id' => $model->id,
                'user_id' => auth()->id(),
            ]);
        });
    }
}
