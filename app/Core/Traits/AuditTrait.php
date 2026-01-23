<?php

declare(strict_types=1);

namespace App\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Audit Trail Trait
 * 
 * Automatically logs model events for auditing purposes
 * Tracks who created, updated, or deleted records
 */
trait AuditTrait
{
    /**
     * Boot the audit trait for a model
     *
     * @return void
     */
    public static function bootAuditTrait(): void
    {
        static::creating(function (Model $model) {
            if (Auth::check() && $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'created_by')) {
                $model->created_by = Auth::id();
            }

            Log::info('Creating record', [
                'model' => get_class($model),
                'user_id' => Auth::id(),
                'data' => $model->getAttributes(),
            ]);
        });

        static::updating(function (Model $model) {
            if (Auth::check() && $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'updated_by')) {
                $model->updated_by = Auth::id();
            }

            Log::info('Updating record', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'user_id' => Auth::id(),
                'original' => $model->getOriginal(),
                'changes' => $model->getChanges(),
            ]);
        });

        static::deleting(function (Model $model) {
            Log::warning('Deleting record', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'user_id' => Auth::id(),
                'data' => $model->getAttributes(),
            ]);
        });

        static::deleted(function (Model $model) {
            Log::warning('Record deleted', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'user_id' => Auth::id(),
            ]);
        });
    }
}
